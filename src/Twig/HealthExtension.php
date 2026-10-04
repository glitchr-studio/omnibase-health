<?php

namespace Base\Health\Twig;

use Base\Health\Entity\PatientProfile;
use Base\Health\Entity\Practice;
use Base\Health\Entity\Practitioner;
use Base\Health\Repository\DependentRepository;
use Base\Health\Repository\FeeRepository;
use Base\Health\Repository\HomeCareRequestRepository;
use Base\Health\Repository\PatientProfileRepository;
use Base\Health\Repository\PracticeRepository;
use Base\Health\Repository\PractitionerRepository;
use Base\Health\Service\CareTeam;
use Base\Office\Entity\Member;
use Base\Office\Repository\Booking\AppointmentTypeRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Health in templates: health_practice(), health_practitioner(member),
 * health_fees(practitioner|null), health_emergency() (the banner's
 * numbers), health_profile(user), health_care_team(user),
 * health_types(member) (what can be booked with them),
 * health_pending_care(), health_teleconsultation_referenced().
 */
class HealthExtension extends AbstractExtension
{
    public function __construct(
        private readonly PracticeRepository $practices,
        private readonly PractitionerRepository $practitioners,
        private readonly FeeRepository $fees,
        private readonly PatientProfileRepository $profiles,
        private readonly DependentRepository $dependents,
        private readonly HomeCareRequestRepository $homeCare,
        private readonly AppointmentTypeRepository $types,
        private readonly CareTeam $careTeam,
        #[Autowire('%health.emergency%')] private readonly array $emergency = [],
        #[Autowire('%health.teleconsultation_referenced%')] private readonly bool $referenced = false,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('health_practice', fn (): ?Practice => $this->practices->findMain()),
            new TwigFunction('health_practitioner', fn (?Member $member): ?Practitioner => $this->practitioners->findOneByMember($member)),
            new TwigFunction('health_fees', fn (?Practitioner $practitioner = null): array => null === $practitioner ? $this->fees->findOrdered() : $this->fees->findForPractitioner($practitioner)),
            new TwigFunction('health_emergency', fn (): array => $this->emergency),
            new TwigFunction('health_profile', fn (?object $user): ?PatientProfile => $this->profiles->findOneByUser($user)),
            new TwigFunction('health_dependents', fn (?object $user): array => null === $user ? [] : $this->dependents->findForHolder($user)),
            new TwigFunction('health_care_team', fn (object $user): array => $this->careTeam->members($user)),
            new TwigFunction('health_types', fn (Member $member): array => array_values(array_filter($this->types->findForMember($member), static fn ($t) => 'staff' !== $t->getBookableBy()->value))),
            new TwigFunction('health_pending_care', fn (): int => $this->homeCare->countPending()),
            new TwigFunction('health_teleconsultation_referenced', fn (): bool => $this->referenced),
        ];
    }
}
