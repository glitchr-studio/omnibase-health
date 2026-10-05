<?php

namespace Base\Health\Demo;

use Base\Demo\DemoAccount;
use Base\Demo\DemoAccountProviderInterface;

/**
 * The demonstration accounts of a practice (glitchr/omnibase's `demo`
 * environment): one for each role this bundle and omnibase/office know, with
 * the identifiers the practices' fixtures have always used. The roles of the
 * staff come from two groups, as in a real practice - "Praticiens"
 * (ROLE_PRACTITIONER) and "Secrétariat" (ROLE_SECRETARY), both ROLE_STAFF
 * through the application's role hierarchy; the coordination is the
 * practice's administrator.
 *
 * An application's fixtures take them from Base\Demo\DemoAccountFactory
 * ($accounts->account('docteur', $manager)) and attach what makes them worth
 * signing in as: a team member and an agenda to the practitioners, a file and
 * appointments to the patients. A practice without one of these roles (a
 * nursing practice has no physician) leaves it out: base.demo.exclude.
 *
 * Registered when the installed glitchr/omnibase has the demo environment
 * (config/services.php).
 */
final class HealthDemoAccounts implements DemoAccountProviderInterface
{
    public const PRACTITIONERS = 'Praticiens';
    public const SECRETARIES = 'Secrétariat';
    public const PATIENTS = 'Patients';

    public function getDemoAccounts(): iterable
    {
        yield new DemoAccount('docteur', '@health.demo.docteur.label', '@health.demo.docteur.description', group: self::PRACTITIONERS, groupRoles: ['ROLE_PRACTITIONER'], position: 10);
        yield new DemoAccount('infirmiere', '@health.demo.infirmiere.label', '@health.demo.infirmiere.description', group: self::PRACTITIONERS, groupRoles: ['ROLE_PRACTITIONER'], position: 20);
        yield new DemoAccount('kine', '@health.demo.kine.label', '@health.demo.kine.description', group: self::PRACTITIONERS, groupRoles: ['ROLE_PRACTITIONER'], position: 30);
        yield new DemoAccount('secretariat', '@health.demo.secretariat.label', '@health.demo.secretariat.description', group: self::SECRETARIES, groupRoles: ['ROLE_SECRETARY'], position: 40);
        yield new DemoAccount('coordination', '@health.demo.coordination.label', '@health.demo.coordination.description', roles: ['ROLE_ADMIN'], position: 50);
        yield new DemoAccount('patient', '@health.demo.patient.label', '@health.demo.patient.description', group: self::PATIENTS, position: 60);
        yield new DemoAccount('patiente', '@health.demo.patiente.label', '@health.demo.patiente.description', group: self::PATIENTS, position: 70);
    }
}
