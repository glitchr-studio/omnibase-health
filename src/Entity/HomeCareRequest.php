<?php

namespace Base\Health\Entity;

use App\Entity\User;
use Base\Health\Enum\CareFrequency;
use Base\Health\Enum\CareStatus;
use Base\Health\Repository\HomeCareRequestRepository;
use Base\Office\Entity\Booking\ServiceArea;
use Base\Office\Entity\Member;
use Base\Office\Entity\Share\Document;
use Doctrine\ORM\Mapping as ORM;

/**
 * Care asked at home: where (checked against the practice's service areas
 * before it is accepted at all), what care, how often, from when to when,
 * the prescription deposited in the vault. Accepted by the practice, it
 * becomes a series of appointments in the agenda. The note the patient
 * typed is kept encrypted.
 */
#[ORM\Entity(repositoryClass: HomeCareRequestRepository::class)]
#[ORM\Table(name: 'health_home_care_request')]
#[ORM\Index(columns: ['status', 'createdAt'], name: 'health_home_care_status_idx')]
class HomeCareRequest
{
    public const CARE_TYPES = ['injection', 'dressing', 'blood_test', 'hygiene', 'infusion', 'monitoring', 'medication', 'other'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?User $patient = null;

    #[ORM\ManyToOne(targetEntity: Dependent::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Dependent $dependent = null;

    #[ORM\Column(length: 255)]
    protected string $street = '';

    #[ORM\Column(length: 16)]
    protected string $postalCode = '';

    #[ORM\Column(length: 120)]
    protected string $city = '';

    #[ORM\Column(length: 30, nullable: true)]
    protected ?string $phone = null;

    #[ORM\Column(length: 24)]
    protected string $careType = 'other';

    #[ORM\Column(length: 24, enumType: CareFrequency::class)]
    protected CareFrequency $frequency = CareFrequency::ONCE;

    #[ORM\Column(type: 'date_immutable')]
    protected \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?\DateTimeImmutable $endDate = null;

    /** "morning", "afternoon", or a time the patient prefers ("08:00"). */
    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $preferredTime = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Document $prescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $notesCipher = null;

    #[ORM\Column(length: 16, enumType: CareStatus::class)]
    protected CareStatus $status = CareStatus::PENDING;

    #[ORM\ManyToOne(targetEntity: ServiceArea::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?ServiceArea $area = null;

    #[ORM\ManyToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Member $assignedTo = null;

    /** The appointments it became (Appointment::$series). */
    #[ORM\Column(length: 36, nullable: true)]
    protected ?string $series = null;

    #[ORM\Column(type: 'utc_datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'utc_datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $decidedAt = null;

    public function __construct(?User $patient = null)
    {
        $this->patient = $patient;
        $this->startDate = new \DateTimeImmutable('tomorrow');
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('#%d · %s %s', $this->id, $this->postalCode, $this->city);
    }

    public function getId(): ?int { return $this->id; }
    public function getPatient(): ?User { return $this->patient; }
    public function setPatient(?User $patient): self { $this->patient = $patient; return $this; }
    public function getDependent(): ?Dependent { return $this->dependent; }
    public function setDependent(?Dependent $dependent): self { $this->dependent = $dependent; return $this; }
    public function getStreet(): string { return $this->street; }
    public function setStreet(?string $street): self { $this->street = trim((string) $street); return $this; }
    public function getPostalCode(): string { return $this->postalCode; }
    public function setPostalCode(?string $code): self { $this->postalCode = (string) preg_replace('/\s+/', '', (string) $code); return $this; }
    public function getCity(): string { return $this->city; }
    public function setCity(?string $city): self { $this->city = trim((string) $city); return $this; }
    public function getAddress(): string { return trim($this->street.', '.$this->postalCode.' '.$this->city, ', '); }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone ?: null; return $this; }
    public function getCareType(): string { return $this->careType; }
    public function setCareType(?string $type): self { $this->careType = \in_array($type, self::CARE_TYPES, true) ? $type : 'other'; return $this; }
    public function getFrequency(): CareFrequency { return $this->frequency; }
    public function setFrequency(CareFrequency|string $frequency): self { $this->frequency = $frequency instanceof CareFrequency ? $frequency : (CareFrequency::tryFrom($frequency) ?? CareFrequency::ONCE); return $this; }
    public function getStartDate(): \DateTimeImmutable { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $date): self { $this->startDate = \DateTimeImmutable::createFromInterface($date); return $this; }
    public function getEndDate(): ?\DateTimeImmutable { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $date): self { $this->endDate = $date ? \DateTimeImmutable::createFromInterface($date) : null; return $this; }
    public function getPreferredTime(): ?string { return $this->preferredTime; }
    public function setPreferredTime(?string $time): self { $this->preferredTime = $time ?: null; return $this; }
    public function getPrescription(): ?Document { return $this->prescription; }
    public function setPrescription(?Document $document): self { $this->prescription = $document; return $this; }
    public function getNotesCipher(): ?string { return $this->notesCipher; }
    public function setNotesCipher(?string $cipher): self { $this->notesCipher = $cipher ?: null; return $this; }
    public function getStatus(): CareStatus { return $this->status; }
    public function setStatus(CareStatus $status): self { $this->status = $status; if (CareStatus::PENDING !== $status) { $this->decidedAt ??= new \DateTimeImmutable(); } return $this; }
    public function getStatusValue(): string { return $this->status->value; }
    public function isPending(): bool { return CareStatus::PENDING === $this->status; }
    public function getArea(): ?ServiceArea { return $this->area; }
    public function setArea(?ServiceArea $area): self { $this->area = $area; return $this; }
    public function getAssignedTo(): ?Member { return $this->assignedTo; }
    public function setAssignedTo(?Member $member): self { $this->assignedTo = $member; return $this; }
    public function getSeries(): ?string { return $this->series; }
    public function setSeries(?string $series): self { $this->series = $series; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getDecidedAt(): ?\DateTimeImmutable { return $this->decidedAt; }

    /** How many visits it asks for. */
    public function getVisitCount(): int
    {
        return $this->frequency->count($this->startDate, $this->endDate);
    }
}
