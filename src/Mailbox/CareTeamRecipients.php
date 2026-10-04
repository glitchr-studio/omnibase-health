<?php

namespace Base\Health\Mailbox;

use App\Entity\User;
use Base\Health\Service\CareTeam;
use Base\Mailbox\Recipient\RecipientProviderInterface;

/**
 * Whom a patient may write to besides the secretariat's desk: the
 * practitioners who follow them - by name, never by a username to guess.
 */
final class CareTeamRecipients implements RecipientProviderInterface
{
    public function __construct(private readonly CareTeam $careTeam)
    {
    }

    public function recipients(User $sender): array
    {
        $recipients = [];
        foreach ($this->careTeam->members($sender) as $member) {
            if (null !== $member->getUser()) {
                $recipients[$member->getDisplayName()] = $member->getUser();
            }
        }

        return $recipients;
    }
}
