<?php

namespace Base\Health\Admin\Widget;

use Base\Admin\Config\Menu\MenuItem;
use Base\Admin\Widget\DashboardWidgetTypeInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The dashboard's "Messages": what waits at the desks the member answers
 * at (the secretariat's), and their own unread conversations. Counts only:
 * a message is read in the mailbox, not on a dashboard someone may see
 * over a shoulder.
 */
final class InboxWidgetType implements DashboardWidgetTypeInterface
{
    /**
     * @param \Base\Mailbox\Service\Mailbox|null $mailbox
     * @param \Base\Mailbox\Desk\Desks|null $desks
     */
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: 'health.mailbox')] private readonly ?object $mailbox = null,
        #[Autowire(service: 'health.mailbox_desks')] private readonly ?object $desks = null,
    ) {
    }

    public static function getName(): string
    {
        return 'health_inbox';
    }

    public function getTemplate(): string
    {
        return '@Health/admin/widget/inbox.html.twig';
    }

    public function getTemplateVars(MenuItem $widget): array
    {
        $user = $this->security->getUser();
        $available = null !== $user && null !== $this->mailbox && method_exists($this->mailbox, 'countUnread');

        return [
            'available' => $available,
            'unread' => $available ? $this->mailbox->countUnread($user) : 0,
            'waiting' => $available && null !== $this->desks && method_exists($this->desks, 'countWaiting') ? $this->desks->countWaiting($user) : 0,
        ];
    }
}
