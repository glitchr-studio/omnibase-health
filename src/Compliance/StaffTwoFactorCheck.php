<?php

namespace Base\Health\Compliance;

use App\Entity\User;
use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;
use Base\Service\SecurityPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Every staff account signs in with a second factor: the application requires
 * it of the staff's role through glitchr/omnibase
 * (base.security.two_factor.required_roles: [ROLE_STAFF], postpone: false -
 * docs/secrecy.md), which sends an account without one to its enrolment page.
 * Missing while nothing requires it; a warning while accounts have none yet.
 */
final class StaffTwoFactorCheck implements ComplianceCheckInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SecurityPolicy $policy, private readonly RoleHierarchyInterface $roles, #[Autowire('%health.roles.staff%')] private readonly string $staffRole = 'ROLE_STAFF')
    {
    }

    public function check(): ComplianceResult
    {
        if (!$this->isRequiredOfTheStaff()) {
            return new ComplianceResult('compliance.staff_2fa', ComplianceResult::MISSING, 'compliance.staff_2fa_off', 'health', ['role' => $this->staffRole]);
        }
        $without = 0;
        foreach ($this->entityManager->getRepository(User::class)->findAll() as $user) {
            if (\in_array($this->staffRole, $this->roles->getReachableRoleNames($user->getRoles()), true) && !$this->policy->hasSecondFactor($user)) {
                ++$without;
            }
        }

        return 0 === $without ? ComplianceResult::ok('compliance.staff_2fa', 'health') : new ComplianceResult('compliance.staff_2fa', ComplianceResult::WARNING, 'compliance.staff_2fa_advice', 'health', ['count' => $without]);
    }

    /**
     * Required of everyone (the administrator's setting), or of a role every
     * staff account holds: the staff's own, or one it gives through the
     * hierarchy.
     */
    private function isRequiredOfTheStaff(): bool
    {
        if (!$this->policy->isTwoFactorAvailable()) {
            return false;
        }

        return $this->policy->isTwoFactorMandatory()
            || [] !== array_intersect($this->policy->getRequiredRoles(), $this->roles->getReachableRoleNames([$this->staffRole]));
    }
}
