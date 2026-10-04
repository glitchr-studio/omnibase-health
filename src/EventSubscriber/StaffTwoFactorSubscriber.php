<?php

namespace Base\Health\EventSubscriber;

use Base\Service\SecurityPolicy;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A second factor is mandatory for the staff (health.staff_two_factor):
 * whoever reads patients' data signs in with more than a password. A staff
 * account without one is sent to its security settings on every page it
 * asks for, until it has one - no "later" for them.
 *
 * omnibase's own rule (SecurityPolicy) is for everyone or no one; this one
 * is for a role, patients are left free to choose.
 */
final class StaffTwoFactorSubscriber
{
    private const EXEMPT_PREFIXES = ['/settings', '/login', '/logout', '/reset-password', '/connect/', '/_', '/rescue'];

    public function __construct(
        private readonly Security $security,
        private readonly SecurityPolicy $policy,
        private readonly UrlGeneratorInterface $urls,
        private readonly TranslatorInterface $translator,
        #[Autowire('%health.staff_two_factor%')] private readonly bool $enabled = true,
        #[Autowire('%health.roles.staff%')] private readonly string $staffRole = 'ROLE_STAFF',
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 3)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if (!$request->isMethod('GET') || $request->isXmlHttpRequest() || 'html' !== $request->getPreferredFormat('html')) {
            return;
        }
        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($request->getPathInfo(), $prefix)) {
                return;
            }
        }
        $user = $this->security->getUser();
        if (null === $user || !$this->security->isGranted($this->staffRole) || $this->policy->hasSecondFactor($user)) {
            return;
        }
        // Someone impersonating a staff account (a super-admin's support) is not the one to enrol.
        if ($this->security->isGranted('IS_IMPERSONATOR')) {
            return;
        }

        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('warning', $this->translator->trans('security.two_factor_required', [], 'health'));
        }
        $event->setResponse(new RedirectResponse($this->urls->generate('user_settings')));
    }
}
