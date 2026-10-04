<?php

namespace Base\Health\Service;

use App\Entity\User;
use Base\Health\Repository\DependentRepository;
use Base\Health\Repository\HomeCareRequestRepository;
use Base\Health\Repository\IdentityLinkRepository;
use Base\Health\Repository\PatientProfileRepository;
use Base\Office\Booking\Booker;
use Base\Office\Exception\BookingException;
use Base\Office\Repository\Booking\AppointmentRepository;
use Base\Office\Repository\Share\AccessLogRepository;
use Base\Office\Repository\Share\DocumentRepository;
use Base\Office\Share\Cipher;
use Base\Office\Share\DocumentVault;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A patient's rights over their data (GDPR art. 15, 17, 20): everything
 * the practice's site holds about them as one JSON document, or a zip with
 * their documents decrypted; and erasure - what identifies them goes at
 * once, what must be kept (past appointments, the access log) stays, bare,
 * until health:purge's retention runs out.
 */
class PatientData
{
    public function __construct(
        private readonly PatientProfileRepository $profiles,
        private readonly DependentRepository $dependents,
        private readonly HomeCareRequestRepository $homeCare,
        private readonly IdentityLinkRepository $links,
        private readonly AppointmentRepository $appointments,
        private readonly DocumentRepository $documents,
        private readonly AccessLogRepository $logs,
        private readonly DocumentVault $vault,
        private readonly Cipher $cipher,
        private readonly Booker $booker,
        private readonly HomeCare $care,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function export(User $user): array
    {
        $profile = $this->profiles->findOneByUser($user);
        $date = static fn (?\DateTimeInterface $d, string $format = \DATE_ATOM) => $d?->format($format);

        return [
            'exported_at' => $date(new \DateTimeImmutable()),
            'account' => ['username' => method_exists($user, 'getUsername') ? $user->getUsername() : null, 'email' => $user->getEmail()],
            'profile' => $profile ? [
                'given_names' => $profile->getGivenNames(),
                'family_name' => $profile->getFamilyName(),
                'birth_date' => $date($profile->getBirthDate(), 'Y-m-d'),
                'phone' => $profile->getPhone(),
                'address' => $profile->getAddress(),
                'referring_physician' => $profile->getReferringPhysician() ? (string) $profile->getReferringPhysician() : null,
                'sharing_opposition' => $profile->isSharingOpposition(),
            ] : null,
            'dependents' => array_map(static fn ($d) => ['given_name' => $d->getGivenName(), 'family_name' => $d->getFamilyName(), 'birth_date' => $date($d->getBirthDate(), 'Y-m-d'), 'relation' => $d->getRelation()->value], $this->dependents->findForHolder($user)),
            'identities' => array_map(static fn ($l) => ['provider' => $l->getProvider(), 'linked_at' => $date($l->getCreatedAt()), 'last_used_at' => $date($l->getLastUsedAt())], $this->links->findForUser($user)),
            'appointments' => array_map(fn ($a) => [
                'starts_at' => $date($a->getStartsAt()),
                'with' => (string) $a->getMember(),
                'type' => (string) $a->getType(),
                'channel' => $a->getChannel()->value,
                'status' => $a->getStatus()->value,
                'for' => $a->getBeneficiary(),
                'address' => $a->getAddress(),
                'reason' => $this->cipher->decryptText($a->getReasonCipher()),
            ], [...$this->appointments->findForClient($user, true, 500), ...$this->appointments->findForClient($user, false, 500)]),
            'home_care_requests' => array_map(fn ($r) => [
                'created_at' => $date($r->getCreatedAt()),
                'address' => $r->getAddress(),
                'care' => $r->getCareType(),
                'frequency' => $r->getFrequency()->value,
                'from' => $date($r->getStartDate(), 'Y-m-d'),
                'until' => $date($r->getEndDate(), 'Y-m-d'),
                'status' => $r->getStatus()->value,
                'notes' => $this->care->notes($r),
            ], $this->homeCare->findForPatient($user)),
            'documents' => array_map(fn ($d) => [
                'id' => $d->getId(),
                'title' => $this->vault->title($d),
                'file' => $this->vault->filename($d),
                'kind' => $d->getKind()->value,
                'from' => $d->getSender() ? (string) $d->getSender() : null,
                'received_at' => $date($d->getCreatedAt()),
                'read_at' => $date($d->getReadAt()),
                'revoked_at' => $date($d->getRevokedAt()),
            ], $this->documents->findForRecipient($user, true)),
            'document_access_log' => array_map(static fn ($l) => ['at' => $date($l->getCreatedAt()), 'document' => $l->getDocumentId(), 'action' => $l->getAction()->value, 'by' => $l->getUserLabel()], $this->logs->findForRecipient((int) $user->getId(), 1000)),
        ];
    }

    /** A zip - data.json and each available document, decrypted - written to a temporary file whose path is returned. */
    public function zip(User $user): string
    {
        $path = tempnam(sys_get_temp_dir(), 'patient-data-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('data.json', json_encode($this->export($user), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        foreach ($this->documents->findForRecipient($user) as $document) {
            if ($document->isAvailable()) {
                $zip->addFromString(sprintf('documents/%d-%s', $document->getId(), $this->vault->filename($document)), $this->vault->read($document));
            }
        }
        $zip->close();

        return $path;
    }

    /**
     * Erased: the profile emptied, the dependants removed, the upcoming
     * appointments cancelled, the pending home care requests cancelled, the
     * documents the patient deposited destroyed, those sent to them
     * withdrawn. The account itself is closed from its own settings.
     */
    public function erase(User $user): void
    {
        foreach ($this->appointments->findForClient($user, true, 500) as $appointment) {
            try {
                $this->booker->cancel($appointment, true);
            } catch (BookingException) {
                // Already not active.
            }
        }
        foreach ($this->homeCare->findForPatient($user) as $request) {
            $this->care->cancel($request);
            $request->setStreet('')->setCity('')->setPostalCode('')->setPhone(null)->setNotesCipher(null);
        }
        foreach ($this->dependents->findForHolder($user) as $dependent) {
            $this->entityManager->remove($dependent);
        }
        foreach ($this->documents->findForRecipient($user, true) as $document) {
            if (null !== $document->getSender() && $document->getSender()->getId() === $user->getId()) {
                $this->vault->destroy($document);
            } elseif (!$document->isRevoked()) {
                $this->vault->revoke($document, $user);
            }
        }
        $this->profiles->findOneByUser($user)?->erase();
        $this->entityManager->flush();
    }
}
