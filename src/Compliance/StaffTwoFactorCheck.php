<?php

namespace Base\Health\Compliance;

use App\Entity\User;
use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;
use Base\Service\SecurityPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/** Every staff account signs in with a second factor. */
final class StaffTwoFactorCheck implements ComplianceCheckInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SecurityPolicy $policy, private readonly RoleHierarchyInterface $roles, #[Autowire('%health.staff_two_factor%')] private readonly bool $enabled = true, #[Autowire('%health.roles.staff%')] private readonly string $staffRole = 'ROLE_STAFF')
    {
    }

    public function check(): ComplianceResult
    {
        if (!$this->enabled) {
            return new ComplianceResult('compliance.staff_2fa', ComplianceResult::MISSING, 'compliance.staff_2fa_off', 'health');
        }
        $without = 0;
        foreach ($this->entityManager->getRepository(User::class)->findAll() as $user) {
            if (\in_array($this->staffRole, $this->roles->getReachableRoleNames($user->getRoles()), true) && !$this->policy->hasSecondFactor($user)) {
                ++$without;
            }
        }

        return 0 === $without ? ComplianceResult::ok('compliance.staff_2fa', 'health') : new ComplianceResult('compliance.staff_2fa', ComplianceResult::WARNING, 'compliance.staff_2fa_advice', 'health', ['count' => $without]);
    }
}
