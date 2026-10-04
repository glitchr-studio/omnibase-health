<?php

namespace Base\Health\Service;

use App\Entity\User;
use Base\Health\Entity\PatientProfile;
use Base\Health\Repository\PatientProfileRepository;
use Base\Office\Event\ClientAccountEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** A patient's profile, made when first needed - or when the account opens from the practice's invitation. */
class PatientProfiles
{
    public function __construct(
        private readonly PatientProfileRepository $profiles,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function of(User $user): PatientProfile
    {
        $profile = $this->profiles->findOneByUser($user);
        if (null === $profile) {
            $profile = new PatientProfile($user);
            $this->entityManager->persist($profile);
            $this->entityManager->flush();
        }

        return $profile;
    }

    #[AsEventListener(event: ClientAccountEvent::CREATED)]
    public function onAccountCreated(ClientAccountEvent $event): void
    {
        if (null !== $this->profiles->findOneByUser($event->user)) {
            return;
        }
        // "Camille Exemple": the last word is the family name, as the secretary typed it.
        $parts = preg_split('/\s+/', trim($event->invitation->getName())) ?: [];
        $family = \count($parts) > 1 ? (string) array_pop($parts) : '';
        $profile = new PatientProfile($event->user, implode(' ', $parts), $family);
        $profile->setPhone($event->invitation->getPhone());
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
    }
}
