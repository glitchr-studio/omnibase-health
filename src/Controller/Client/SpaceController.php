<?php

namespace Base\Health\Controller\Client;

use App\Entity\User;
use Base\Health\Entity\Dependent;
use Base\Health\Enum\Relation;
use Base\Health\Repository\DependentRepository;
use Base\Health\Repository\HomeCareRequestRepository;
use Base\Health\Repository\PractitionerRepository;
use Base\Health\Service\CareTeam;
use Base\Health\Service\HomeCare;
use Base\Health\Service\PatientData;
use Base\Health\Service\PatientProfiles;
use Base\Office\Repository\Booking\AppointmentRepository;
use Base\Office\Repository\Share\AccessLogRepository;
use Base\Office\Repository\Share\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The patient's space: what comes next (appointments, a video room to
 * join, documents not read yet, home care asked), who they are for the
 * practice (their profile, their dependants), and their data - to take
 * away or to have erased.
 */
#[IsGranted('ROLE_USER')]
class SpaceController extends AbstractController
{
    public function __construct(
        private readonly PatientProfiles $profiles,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/espace', name: 'health_space', methods: ['GET'])]
    public function index(AppointmentRepository $appointments, DocumentRepository $documents, HomeCareRequestRepository $homeCare, CareTeam $careTeam): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('@Health/client/space/index.html.twig', [
            'profile' => $this->profiles->of($user),
            'upcoming' => $appointments->findForClient($user, true, 5),
            'unread' => $documents->countUnread($user),
            'documents' => \array_slice($documents->findForRecipient($user), 0, 3),
            'requests' => \array_slice($homeCare->findForPatient($user), 0, 3),
            'team' => $careTeam->members($user),
        ]);
    }

    #[Route('/espace/profil', name: 'health_space_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, PractitionerRepository $practitioners): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $this->profiles->of($user);

        if ($request->isMethod('POST')) {
            $this->assertToken($request, 'health_profile');
            $birth = '' !== (string) $request->request->get('birthDate') ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('birthDate')) : null;
            $physician = (int) $request->request->get('referringPhysician') ? $practitioners->find((int) $request->request->get('referringPhysician')) : null;
            $profile
                ->setGivenNames((string) $request->request->get('givenNames'))
                ->setFamilyName((string) $request->request->get('familyName'))
                ->setBirthDate($birth ?: null)
                ->setPhone((string) $request->request->get('phone'))
                ->setStreet((string) $request->request->get('street'))
                ->setPostalCode((string) $request->request->get('postalCode'))
                ->setCity((string) $request->request->get('city'))
                ->setReferringPhysician($physician?->isPhysician() ? $physician : null)
                ->setSharingOpposition($request->request->getBoolean('sharingOpposition'));
            $this->entityManager->flush();
            $this->addFlash('success', $this->translator->trans('space.profile.saved', [], 'health'));

            return $this->redirectToRoute('health_space_profile');
        }

        return $this->render('@Health/client/space/profile.html.twig', ['profile' => $profile, 'physicians' => $practitioners->findPhysicians()]);
    }

    #[Route('/espace/proches', name: 'health_space_dependents', methods: ['GET', 'POST'])]
    public function dependents(Request $request, DependentRepository $dependents): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $this->assertToken($request, 'health_dependents');
            if ($request->request->getInt('remove')) {
                $dependent = $dependents->findOneBy(['id' => $request->request->getInt('remove'), 'holder' => $user]);
                if ($dependent) {
                    $this->entityManager->remove($dependent);
                    $this->entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('space.dependents.removed', [], 'health'));
                }
            } elseif ('' !== trim((string) $request->request->get('givenName'))) {
                $dependent = new Dependent($user, (string) $request->request->get('givenName'), (string) $request->request->get('familyName'), Relation::tryFrom((string) $request->request->get('relation')) ?? Relation::CHILD);
                $dependent->setBirthDate('' !== (string) $request->request->get('birthDate') ? (\DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('birthDate')) ?: null) : null);
                $this->entityManager->persist($dependent);
                $this->entityManager->flush();
                $this->addFlash('success', $this->translator->trans('space.dependents.added', ['name' => $dependent->getGivenName()], 'health'));
            }

            return $this->redirectToRoute('health_space_dependents');
        }

        return $this->render('@Health/client/space/dependents.html.twig', ['dependents' => $dependents->findForHolder($user), 'relations' => Relation::cases()]);
    }

    #[Route('/espace/soins-a-domicile/{id}/annuler', name: 'health_space_care_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancelCare(Request $request, int $id, HomeCareRequestRepository $requests, HomeCare $homeCare): Response
    {
        $this->assertToken($request, 'health_care_'.$id);
        $care = $requests->findOneBy(['id' => $id, 'patient' => $this->getUser()]) ?? throw $this->createNotFoundException();
        $homeCare->cancel($care);
        $this->addFlash('success', $this->translator->trans('home_care.flash.cancelled', [], 'health'));

        return $this->redirectToRoute('health_space');
    }

    /** One's data: what is held, who looked at one's documents, the export, the erasure. */
    #[Route('/espace/donnees', name: 'health_space_data', methods: ['GET'])]
    public function data(AccessLogRepository $logs): Response
    {
        return $this->render('@Health/client/space/data.html.twig', [
            'logs' => $logs->findForRecipient((int) $this->getUser()->getId(), 50),
            'retention' => $this->getParameter('health.retention'),
        ]);
    }

    #[Route('/espace/donnees/export.{format}', name: 'health_space_export', methods: ['POST'], requirements: ['format' => 'json|zip'])]
    public function export(Request $request, string $format, PatientData $data): Response
    {
        $this->assertToken($request, 'health_data');
        /** @var User $user */
        $user = $this->getUser();

        if ('zip' === $format) {
            $response = new BinaryFileResponse($data->zip($user));
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'mes-donnees.zip');
            $response->deleteFileAfterSend(true);
        } else {
            $response = new JsonResponse($data->export($user));
            $response->setEncodingOptions(\JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            $response->headers->set('Content-Disposition', 'attachment; filename="mes-donnees.json"');
        }
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route('/espace/donnees/effacer', name: 'health_space_erase', methods: ['POST'])]
    public function erase(Request $request, PatientData $data): Response
    {
        $this->assertToken($request, 'health_data');
        if ('EFFACER' !== strtoupper(trim((string) $request->request->get('confirm')))) {
            $this->addFlash('warning', $this->translator->trans('space.data.erase_confirm_missing', [], 'health'));

            return $this->redirectToRoute('health_space_data');
        }
        $data->erase($this->getUser());
        $this->addFlash('success', $this->translator->trans('space.data.erased', [], 'health'));

        return $this->redirectToRoute('health_space_data');
    }

    private function assertToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
    }
}
