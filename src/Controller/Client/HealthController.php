<?php

namespace Base\Health\Controller\Client;

use App\Entity\User;
use Base\Health\Entity\HomeCareRequest;
use Base\Health\Enum\CareFrequency;
use Base\Health\Exception\HomeCareException;
use Base\Health\Repository\DependentRepository;
use Base\Health\Repository\FeeRepository;
use Base\Health\Repository\PracticeRepository;
use Base\Health\Service\HomeCare;
use Base\Health\Service\PatientProfiles;
use Base\Office\Enum\Channel;
use Base\Office\Enum\DocumentKind;
use Base\Office\Exception\KeyMissingException;
use Base\Office\Exception\ShareException;
use Base\Office\Repository\Booking\AppointmentTypeRepository;
use Base\Office\Repository\Booking\ServiceAreaRepository;
use Base\Office\Repository\MemberRepository;
use Base\Office\Share\DocumentVault;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The health regime's public pages: the fees (which the law has posted),
 * care at home - where the practice goes, and the request -, video
 * consultations, and what to do in an emergency.
 */
class HealthController extends AbstractController
{
    public function __construct(private readonly PracticeRepository $practices, private readonly TranslatorInterface $translator, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/tarifs', name: 'health_fees', methods: ['GET'])]
    public function fees(FeeRepository $fees): Response
    {
        $groups = [];
        foreach ($fees->findOrdered() as $fee) {
            $groups[$fee->getPractitioner() ? (string) $fee->getPractitioner() : ($fee->getCategory() ?? '')][] = $fee;
        }

        return $this->render('@Health/client/fees.html.twig', ['groups' => $groups, 'practice' => $this->practices->findMain()]);
    }

    #[Route('/soins-a-domicile', name: 'health_home_care', methods: ['GET', 'POST'])]
    public function homeCare(Request $request, ServiceAreaRepository $areas, HomeCare $homeCare, DependentRepository $dependents, DocumentVault $vault, PatientProfiles $profiles): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $values = $request->request->all();
        $status = Response::HTTP_OK;

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            if (!$this->isCsrfTokenValid('health_home_care', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            try {
                $start = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('startDate')) ?: throw new HomeCareException('home_care.error.dates');
                $end = '' !== (string) $request->request->get('endDate') ? (\DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('endDate')) ?: throw new HomeCareException('home_care.error.dates')) : null;
                $dependent = (int) $request->request->get('dependent') ? $dependents->findOneBy(['id' => (int) $request->request->get('dependent'), 'holder' => $user]) : null;

                // The address is checked before the prescription is kept: nothing is stored for a request that cannot be taken.
                $care = $homeCare->submit($user, [
                    'street' => $request->request->get('street'), 'postalCode' => $request->request->get('postalCode'), 'city' => $request->request->get('city'),
                    'phone' => $request->request->get('phone'), 'careType' => $request->request->get('careType'), 'frequency' => $request->request->get('frequency'),
                    'startDate' => $start, 'endDate' => $end, 'preferredTime' => $request->request->get('preferredTime'), 'notes' => $request->request->get('notes'),
                ], $dependent);

                $file = $request->files->get('prescription');
                if ($file instanceof UploadedFile && $file->isValid()) {
                    try {
                        $care->setPrescription($vault->deposit($file, $user, $user, $this->translator->trans('home_care.prescription_title', [], 'health'), DocumentKind::PRESCRIPTION, context: 'health:home_care:'.$care->getId()));
                        $this->entityManager->flush();
                    } catch (ShareException|KeyMissingException) {
                        $this->addFlash('warning', $this->translator->trans('home_care.flash.prescription_failed', [], 'health'));
                    }
                }
                $profile = $profiles->of($user);
                if (null === $profile->getStreet()) {
                    $profile->setStreet($care->getStreet())->setPostalCode($care->getPostalCode())->setCity($care->getCity());
                    $this->entityManager->flush();
                }
                $this->addFlash('success', $this->translator->trans('home_care.flash.sent', [], 'health'));

                return $this->redirectToRoute('health_space');
            } catch (HomeCareException $e) {
                $this->addFlash('danger', $this->translator->trans($e->getKey(), $e->getParameters(), 'health'));
                $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            } catch (KeyMissingException) {
                $this->addFlash('danger', $this->translator->trans('home_care.error.unavailable', [], 'health'));
                $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            }
        }

        return $this->render('@Health/client/home_care.html.twig', [
            'practice' => $this->practices->findMain(),
            'areas' => $areas->findActive(),
            'care_types' => HomeCareRequest::CARE_TYPES,
            'frequencies' => CareFrequency::cases(),
            'dependents' => $user ? $dependents->findForHolder($user) : [],
            'profile' => $user ? $profiles->of($user) : null,
            'values' => $values,
        ], new Response(null, $status));
    }

    #[Route('/teleconsultation', name: 'health_teleconsultation', methods: ['GET'])]
    public function teleconsultation(MemberRepository $members, AppointmentTypeRepository $types): Response
    {
        $offers = [];
        foreach ($members->findBookable() as $member) {
            foreach ($types->findForMember($member) as $type) {
                if (Channel::VIDEO === $type->getChannel() && 'staff' !== $type->getBookableBy()->value) {
                    $offers[] = ['member' => $member, 'type' => $type];
                }
            }
        }

        return $this->render('@Health/client/teleconsultation.html.twig', ['offers' => $offers, 'practice' => $this->practices->findMain()]);
    }

    #[Route('/urgences', name: 'health_emergencies', methods: ['GET'])]
    public function emergencies(): Response
    {
        return $this->render('@Health/client/emergencies.html.twig', ['practice' => $this->practices->findMain()]);
    }
}
