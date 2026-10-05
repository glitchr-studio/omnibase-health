---
title: omnibase/health
order: 1
---

# omnibase/health

## Installation

```sh
composer require omnibase/health          # brings omnibase/office
composer require omnibase/mailbox glitchr/omnistate omnistate/annuaire-sante   # suggested
```

```php
// config/bundles.php
Base\Office\OfficeBundle::class => ['all' => true],
Base\Health\HealthBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml - after omnibase/office's
health_controller:
    resource: "@HealthBundle/src/Controller/Client"
    type: attribute
health_admin_controller:
    resource: "@HealthBundle/src/Controller/Admin"
    type: attribute
```

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_PRACTITIONER: [ROLE_STAFF]
        ROLE_SECRETARY:    [ROLE_STAFF]
        ROLE_STAFF:        [ROLE_USER]
        ROLE_ADMIN:        [ROLE_STAFF, ROLE_SECRETARY]    # the practice's coordination
```

The roles are given by omnibase's groups (`Base\Entity\User\Group`): a group "Praticiens" whose
roles are `[ROLE_PRACTITIONER]`, a group "Secrétariat" with `[ROLE_SECRETARY]`. No membership table
of the bundle's own.

Then a migration: 7 tables prefixed `health_`.

## Configuration

```yaml
# config/packages/health.yaml
health:
    roles: { practitioner: ROLE_PRACTITIONER, secretary: ROLE_SECRETARY, staff: ROLE_STAFF, coordination: ROLE_ADMIN }
    emergency: { samu: '15', europe: '112', deaf: '114', pharmacy: '3237', oncall: '116 117' }
    retention:                        # months, for health:purge - to set with the practice's DPO
        appointments: 36
        home_care: 36
        access_logs: 36
        signals: 1
        documents_after_expiry: 1
    teleconsultation_referenced: false   # true only if the video solution is listed by the ANS
```

The bundle sets, for omnibase/office: the team's and the member's templates (practitioners, with
fees and next slots), the contact form's warning ("no medical data here"), the patient space's menu.

## Pages

| Route | Path | |
|---|---|---|
| `health_fees` | `/tarifs` | each practitioner's fees and the practice's |
| `health_home_care` | `/soins-a-domicile` | the areas, the request ([Home care](home-care.md)) |
| `health_teleconsultation` | `/teleconsultation` | who offers video, how it works, what it is not |
| `health_emergencies` | `/urgences` | the numbers, the practice's instructions |
| `health_space…` | `/espace`, `/espace/profil`, `/espace/proches`, `/espace/donnees` | the patient's space ([Patients' data](patients-data.md)) |

In the site's layout, on every page:

```twig
{% include '@Health/partials/_emergency.html.twig' %}
<link rel="stylesheet" href="{{ asset('bundles/health/css/health.css') }}">
```

Twig: `health_practice()`, `health_practitioner(member)`, `health_fees(practitioner)`,
`health_emergency()`, `health_profile(user)`, `health_dependents(user)`, `health_care_team(user)`.

A `Practice` has a type; `isHomeCareFirst()` is true for a nursing practice: a site puts care at home
before appointments on its home page. Nothing in the bundle is written for "a doctor".

## The team read from the Annuaire Santé

A member's `registryId` is their RPPS number. "Lire l'Annuaire Santé" (`PractitionerRegistry::refresh`)
writes the profession and the MSSanté address on the `Practitioner`. The key is the one typed in the
back office (API keys: `api.annuaire_sante.key`) or `omnistate.annuaire_sante.api_key`.

## Back office

CRUD: practice, practitioners, patients, home care requests, fees. `/admin/agenda/soins-a-domicile`:
the requests waiting. Widgets: `health_requests`, `health_inbox` (counts only). Settings: the HDS
host; API keys: the Annuaire Santé's.

## Compliance

Added to omnibase/office's widget: the mailbox's encryption and key, a second factor for every staff
account, fees displayed for every practitioner, out-of-hours instructions, an HDS-certified host
declared, and the reminder that the built-in video is not ANS-listed.

## Cron

```
20 4 * * *  bin/console health:purge     # what has outlived health.retention; --dry-run counts
```

## Demonstration accounts

In glitchr/omnibase's `demo` environment (its `docs/20-architecture/demo.md`) the sign-in page offers
one button for each role of a practice. `Base\Health\Demo\HealthDemoAccounts` declares them:

| Identifier | Role | |
|---|---|---|
| `docteur`, `infirmiere`, `kine` | group "Praticiens" (`ROLE_PRACTITIONER`) | the agenda, the patients, the documents sent |
| `secretariat` | group "Secrétariat" (`ROLE_SECRETARY`) | everyone's agenda, the desk's messages; sends documents, does not read them |
| `coordination` | `ROLE_ADMIN` | the practice's administration: team, fees, access log, compliance |
| `patient`, `patiente` | group "Patients" | appointments, a dependant, a result, a home care request |

The password is the identifier. The fixtures take the accounts from omnibase's factory and attach
what makes them worth signing in as - a team member and an agenda, a file and appointments:

```php
public function __construct(private readonly \Base\Demo\DemoAccountFactory $accounts) {}

$docteur = $this->accounts->account('docteur', $manager);   // created with its group, or the database's
```

A practice without one of these roles leaves it out (`base.demo.exclude: [docteur, kine]` for a
nursing practice); a site that renames one declares the same identifier in its own provider. The
second factor required of the staff (`base.security.two_factor.required_roles`) is lifted for
these accounts in `demo`, and only there. The labels are `demo.<identifier>.label` and
`.description` in the `health` domain.

## Reserved

`Base\Health\Transmission\MssanteTransmitterInterface`: sending to Mon espace santé through MSSanté
takes an operator and a CPS card; the contract is there, no implementation.

## More

[Medical secrecy](secrecy.md) · [Home care](home-care.md) · [Patients' data](patients-data.md) · [Identity](identity.md)
