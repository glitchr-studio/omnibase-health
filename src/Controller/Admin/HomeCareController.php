<?php

namespace Base\Health\Controller\Admin;

use Base\Health\Entity\HomeCareRequest;
use Base\Health\Exception\HomeCareException;
use Base\Health\Repository\HomeCareRequestRepository;
use Base\Health\Service\HomeCare;
use Base\Office\Booking\SlotFinder;
use Base\Office\Controller\Admin\AdminPageTrait;
use Base\Office\Exception\BookingException;
use Base\Office\Repository\MemberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The home care requests waiting, in the back office: who asks, where,
 * what care and how often - then who goes and when the first visit is:
 * accepted, the visits enter the agenda as a series; or refused.
 */
#[IsGranted('ROLE_STAFF')]
class HomeCareController extends AbstractController
{
    use AdminPageTrait;

    public function __construct(
        private readonly HomeCareRequestRepository $requests,
        private readonly HomeCare $homeCare,
        private readonly MemberRepository $members,
        private readonly SlotFinder $slots,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/admin/agenda/soins-a-domicile', name: 'health_admin_home_care', methods: ['GET'], defaults: ['_nest' => true])]
    public function index(): Response
    {
        return $this->page('@Health/admin/home_care.html.twig', [
            'requests' => $this->requests->findPending(50),
            'members' => $this->homeCare->visitors(),
            'timezone' => $this->slots->timezone()->getName(),
        ]);
    }

    #[Route('/admin/agenda/soins-a-domicile/{id}/{action}', name: 'health_admin_home_care_decide', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'accept|refuse'])]
    public function decide(Request $request, HomeCareRequest $care, string $action): Response
    {
        $this->assertToken($request, 'health-home-care-'.$care->getId());
        try {
            if ('refuse' === $action) {
                $this->homeCare->refuse($care);
                $this->addFlash('success', $this->translator->trans('admin.home_care.flash.refused', [], 'health'));
            } else {
                $member = $this->members->find($request->request->getInt('member')) ?? throw new HomeCareException('home_care.error.incomplete');
                $first = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $request->request->getString('date').' '.$request->request->getString('time'), $this->slots->timezone()) ?: throw new HomeCareException('home_care.error.dates');
                $appointments = $this->homeCare->accept($care, $member, $first, $this->getUser());
                $this->addFlash('success', $this->translator->trans('admin.home_care.flash.accepted', ['count' => \count($appointments), 'member' => (string) $member], 'health'));
            }
        } catch (HomeCareException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getKey(), $e->getParameters(), 'health'));
        } catch (BookingException $e) {
            $this->addFlash('danger', $this->translator->trans($e->getKey(), $e->getParameters(), 'office'));
        }

        return $this->redirectToRoute('health_admin_home_care');
    }
}
