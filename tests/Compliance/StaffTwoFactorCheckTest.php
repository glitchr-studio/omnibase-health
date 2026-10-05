<?php

namespace Base\Health\Tests\Compliance;

use App\Entity\User;
use Base\Health\Compliance\StaffTwoFactorCheck;
use Base\Office\Compliance\ComplianceResult;
use Base\Service\SecurityPolicy;
use Base\Service\SettingBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * The staff's second factor is glitchr/omnibase's rule
 * (base.security.two_factor.required_roles): the check says it is missing
 * while nothing requires it of the staff, counts the staff accounts that
 * have none yet, and is satisfied once each has one.
 */
final class StaffTwoFactorCheckTest extends TestCase
{
    public function testMissingWhileNothingRequiresItOfTheStaff(): void
    {
        $result = $this->check(requiredRoles: [], accounts: [$this->account(['ROLE_PRACTITIONER'], false)])->check();

        self::assertSame(ComplianceResult::MISSING, $result->status);
        self::assertSame('compliance.staff_2fa_off', $result->advice);
        self::assertSame(['role' => 'ROLE_STAFF'], $result->parameters);
    }

    public function testARoleTheStaffDoNotAllHoldDoesNotCount(): void
    {
        // Required of the practitioners only: the secretariat is staff too, and nothing asks it.
        $result = $this->check(requiredRoles: ['ROLE_PRACTITIONER'], accounts: [])->check();

        self::assertSame(ComplianceResult::MISSING, $result->status);
    }

    public function testAWarningWhileStaffAccountsHaveNone(): void
    {
        $result = $this->check(requiredRoles: ['ROLE_STAFF'], accounts: [
            $this->account(['ROLE_PRACTITIONER'], false),
            $this->account(['ROLE_SECRETARY'], true),
            $this->account(['ROLE_USER'], false), // a patient chooses
        ])->check();

        self::assertSame(ComplianceResult::WARNING, $result->status);
        self::assertSame(['count' => 1], $result->parameters);
    }

    public function testSatisfiedOnceEveryStaffAccountHasOne(): void
    {
        $result = $this->check(requiredRoles: ['ROLE_STAFF'], accounts: [
            $this->account(['ROLE_PRACTITIONER'], true),
            $this->account(['ROLE_USER'], false),
        ])->check();

        self::assertTrue($result->isOk());
    }

    public function testARoleUnderTheStaffsCoversThem(): void
    {
        // ROLE_STAFF gives ROLE_USER: required of ROLE_USER, it is required of the staff.
        $result = $this->check(requiredRoles: ['ROLE_USER'], accounts: [$this->account(['ROLE_SECRETARY'], true)])->check();

        self::assertTrue($result->isOk());
    }

    /**
     * @param list<string> $requiredRoles
     * @param list<User>   $accounts
     */
    private function check(array $requiredRoles, array $accounts): StaffTwoFactorCheck
    {
        $hierarchy = new RoleHierarchy(['ROLE_PRACTITIONER' => ['ROLE_STAFF'], 'ROLE_SECRETARY' => ['ROLE_STAFF'], 'ROLE_STAFF' => ['ROLE_USER']]);

        // No setting saved: a second factor is offered, and mandatory for nobody.
        $settings = $this->createStub(SettingBagInterface::class);
        $settings->method('getScalar')->willReturn(null);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findAll')->willReturn($accounts);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return new StaffTwoFactorCheck($entityManager, new SecurityPolicy($settings, $hierarchy, $requiredRoles, false), $hierarchy);
    }

    /** @param list<string> $roles */
    private function account(array $roles, bool $secondFactor): User
    {
        $user = $this->createStub(User::class);
        $user->method('getRoles')->willReturn($roles);
        $user->method('isTotpAuthenticationEnabled')->willReturn($secondFactor);
        $user->method('isEmailAuthEnabled')->willReturn(false);

        return $user;
    }
}
