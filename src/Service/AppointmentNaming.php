<?php

namespace Base\Health\Service;

use Base\Health\Repository\PatientProfileRepository;
use Base\Office\Event\AppointmentEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * An appointment booked online carries the patient's name as their profile
 * writes it ("Camille ÉRABLE"), not the account's identifier: it is what
 * the agenda shows the staff.
 */
final class AppointmentNaming
{
    public function __construct(private readonly PatientProfileRepository $profiles, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[AsEventListener(event: AppointmentEvent::BOOKED, priority: 10)]
    #[AsEventListener(event: AppointmentEvent::REQUESTED, priority: 10)]
    public function name(AppointmentEvent $event): void
    {
        foreach ($event->series ?: [$event->appointment] as $appointment) {
            $profile = $this->profiles->findOneByUser($appointment->getClient());
            if (null !== $profile && '' !== $profile->getFamilyName() && null === $appointment->getMetaValue('named')) {
                $appointment->setClientName($profile->getFullName())->setClientPhone($appointment->getClientPhone() ?? $profile->getPhone())->setMetaValue('named', true);
            }
        }
        $this->entityManager->flush();
    }
}
