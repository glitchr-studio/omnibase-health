<?php

namespace Base\Health\Tests\Demo;

use Base\Demo\DemoAccountRegistry;
use Base\Health\Demo\HealthDemoAccounts;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Yaml\Yaml;

/**
 * The demonstration accounts of a practice: one for each role, the staff's
 * roles through their groups, a label and a sentence for each in the
 * bundle's catalogue - and nobody above the practice's administrator.
 */
class HealthDemoAccountsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(DemoAccountRegistry::class)) {
            self::markTestSkipped('Requires a glitchr/omnibase with the demo environment.');
        }
    }

    /** The role hierarchy the bundle's documentation gives an application (docs/index.md). */
    private function registry(): DemoAccountRegistry
    {
        return new DemoAccountRegistry([new HealthDemoAccounts()], [], new RoleHierarchy([
            'ROLE_PRACTITIONER' => ['ROLE_STAFF'],
            'ROLE_SECRETARY' => ['ROLE_STAFF'],
            'ROLE_STAFF' => ['ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_STAFF', 'ROLE_SECRETARY'],
            'ROLE_SUPERADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
            'ROLE_EDITOR' => ['ROLE_SUPERADMIN'],
        ]));
    }

    public function testOneAccountForEachRoleOfThePractice(): void
    {
        $accounts = $this->registry()->all();

        $this->assertSame(['docteur', 'infirmiere', 'kine', 'secretariat', 'coordination', 'patient', 'patiente'], array_keys($accounts));
        foreach (['docteur', 'infirmiere', 'kine'] as $practitioner) {
            $this->assertSame(HealthDemoAccounts::PRACTITIONERS, $accounts[$practitioner]->group);
            $this->assertSame(['ROLE_USER', 'ROLE_PRACTITIONER'], $accounts[$practitioner]->getAllRoles());
        }
        $this->assertSame(['ROLE_USER', 'ROLE_SECRETARY'], $accounts['secretariat']->getAllRoles());
        $this->assertSame(['ROLE_ADMIN'], $accounts['coordination']->getAllRoles());
        $this->assertSame(['ROLE_USER'], $accounts['patient']->getAllRoles(), 'a patient holds no role of the practice');
        $this->assertSame('docteur', $accounts['docteur']->getPassword(), 'the password is the identifier, as in the fixtures');
    }

    public function testNobodyAboveThePracticesAdministrator(): void
    {
        $registry = $this->registry();
        foreach ($registry->all() as $account) {
            $this->assertFalse($registry->reachesSuperAdmin($account->getAllRoles()), $account->identifier);
        }
    }

    public function testEachHasItsLabelAndItsSentenceInTheCatalogue(): void
    {
        $catalogue = Yaml::parseFile(\dirname(__DIR__, 2).'/translations/health+intl-icu.fr.yaml')['demo'];

        foreach ($this->registry()->all() as $identifier => $account) {
            $this->assertSame('@health.demo.'.$identifier.'.label', $account->label);
            $this->assertSame('@health.demo.'.$identifier.'.description', $account->description);
            $this->assertNotEmpty($catalogue[$identifier]['label'] ?? null, $identifier);
            $this->assertNotEmpty($catalogue[$identifier]['description'] ?? null, $identifier);
        }
    }
}
