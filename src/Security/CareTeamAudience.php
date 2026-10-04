<?php

namespace Base\Health\Security;

use App\Entity\User;
use Base\Health\Service\CareTeam;
use Base\Office\Entity\Share\Document;
use Base\Office\Share\AudienceResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Medical secrecy, as the practice's tool applies it to a patient's
 * documents (beyond the patient and the author, who always read theirs):
 *
 * - a practitioner who follows the patient (Base\Health\Service\CareTeam)
 *   reads them - unless the patient objected to sharing within the team;
 * - the secretariat files and sends: it sees that a document exists and
 *   what it is called, not what it says;
 * - a document not marked confidential (an administrative letter) is read
 *   by the staff;
 * - nobody else: neither another patient, nor the site's administrators as
 *   such.
 */
final class CareTeamAudience implements AudienceResolverInterface
{
    public function __construct(
        private readonly CareTeam $careTeam,
        private readonly RoleHierarchyInterface $roles,
        #[Autowire('%health.roles.practitioner%')] private readonly string $practitionerRole = 'ROLE_PRACTITIONER',
        #[Autowire('%health.roles.secretary%')] private readonly string $secretaryRole = 'ROLE_SECRETARY',
        #[Autowire('%health.roles.staff%')] private readonly string $staffRole = 'ROLE_STAFF',
    ) {
    }

    public function decide(string $attribute, Document $document, UserInterface $user): ?bool
    {
        $patient = $document->getRecipient();
        if (!$user instanceof User || null === $patient || self::REVOKE === $attribute) {
            return null;
        }
        $held = $this->roles->getReachableRoleNames($user->getRoles());
        if (!\in_array($this->staffRole, $held, true)) {
            return null;
        }

        $follows = \in_array($this->practitionerRole, $held, true) && $this->careTeam->follows($user, $patient) && !$this->careTeam->hasOpposition($patient);
        if (self::VIEW === $attribute) {
            return $follows || \in_array($this->secretaryRole, $held, true) ? true : null;
        }

        // READ
        return $follows || !$document->isConfidential() ? true : null;
    }
}
