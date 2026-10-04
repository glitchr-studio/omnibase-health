<?php

namespace Base\Health\Service;

use Base\Health\Entity\Practitioner;
use Base\Office\Service\MemberRegistry;
use Base\Service\SettingBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * "Lire l'Annuaire Santé": a practitioner read from the ANS's directory by
 * their RPPS number (glitchr/omnistate with omnistate/annuaire-sante), their
 * profession and MSSanté address written on the record. The key is the one
 * typed in the back office (API keys) if there is one, else the
 * configuration's (omnistate.annuaire_sante.api_key).
 */
class PractitionerRegistry
{
    public const KEY_SETTING = 'api.annuaire_sante.key';

    public function __construct(
        private readonly MemberRegistry $members,
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingBagInterface $settings,
        private readonly ?HttpClientInterface $http = null,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->members->isAvailable();
    }

    /**
     * @return \Omnistate\Model\Professional|null null: no RPPS on the record, or the directory does not know it
     *
     * @throws \Omnistate\Exception\OmnistateException the directory did not answer, or no key
     */
    public function refresh(Practitioner $practitioner): ?object
    {
        $member = $practitioner->getMember();
        if (null === $member || null === $member->getRegistryId()) {
            return null;
        }

        $key = $this->key();
        if (null !== $key && null !== $this->http && class_exists(\Omnistate\AnnuaireSante\AnnuaireSante::class)) {
            $professional = (new \Omnistate\AnnuaireSante\AnnuaireSante($this->http, $key))->professional($member->getRegistryId());
            $member->setRegistryData(null === $professional ? ['found' => false] : MemberRegistry::snapshot($professional));
        } else {
            $professional = $this->members->refresh($member);
        }

        if (null !== $professional) {
            if (null !== $professional->profession) {
                $practitioner->setProfessionCode($professional->profession->code)->setProfession($professional->profession->label ?? $practitioner->getProfession());
            }
            if (null === $practitioner->getSpecialty() && isset($professional->specialties[0]) && null !== $professional->specialties[0]->label) {
                $practitioner->setSpecialty($professional->specialties[0]->label);
            }
            if (isset($professional->secureEmails[0])) {
                $practitioner->setMssante($professional->secureEmails[0]);
            }
        }
        $this->entityManager->flush();

        return $professional;
    }

    private function key(): ?string
    {
        try {
            return trim((string) $this->settings->getScalar(self::KEY_SETTING)) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
