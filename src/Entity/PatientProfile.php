<?php

namespace Base\Health\Entity;

use App\Entity\User;
use Base\Health\Repository\PatientProfileRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * What the practice needs to know of an account to receive its holder: who
 * they are, how to reach them, where they live (home visits), whether one
 * of the practice's physicians is their "médecin traitant", and whether
 * they object to their documents being shared within the care team. Not a
 * medical record: nothing clinical is kept here.
 */
#[ORM\Entity(repositoryClass: PatientProfileRepository::class)]
#[ORM\Table(name: 'health_patient_profile')]
class PatientProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(length: 120)]
    protected string $familyName = '';

    #[ORM\Column(length: 160)]
    protected string $givenNames = '';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 30, nullable: true)]
    protected ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $street = null;

    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $postalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $city = null;

    #[ORM\ManyToOne(targetEntity: Practitioner::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Practitioner $referringPhysician = null;

    /** The patient objects to the care team reading their documents: only the author and they do. */
    #[ORM\Column(type: 'boolean')]
    protected bool $sharingOpposition = false;

    #[ORM\Column(type: 'utc_datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    /** Asked to be erased: what must be kept stays until health:purge, the rest is gone. */
    #[ORM\Column(type: 'utc_datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $erasedAt = null;

    public function __construct(?User $user = null, string $givenNames = '', string $familyName = '')
    {
        $this->user = $user;
        $this->givenNames = $givenNames;
        $this->familyName = $familyName;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getFamilyName(): string { return $this->familyName; }
    public function setFamilyName(?string $name): self { $this->familyName = trim((string) $name); return $this; }
    public function getGivenNames(): string { return $this->givenNames; }
    public function setGivenNames(?string $names): self { $this->givenNames = trim((string) $names); return $this; }
    public function getFullName(): string { return trim($this->givenNames.' '.mb_strtoupper($this->familyName)); }
    public function getBirthDate(): ?\DateTimeImmutable { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $date): self { $this->birthDate = $date ? \DateTimeImmutable::createFromInterface($date) : null; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone ?: null; return $this; }
    public function getStreet(): ?string { return $this->street; }
    public function setStreet(?string $street): self { $this->street = $street ?: null; return $this; }
    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $code): self { $this->postalCode = $code ? preg_replace('/\s+/', '', $code) : null; return $this; }
    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city ?: null; return $this; }
    public function getAddress(): string { return trim(implode(', ', array_filter([$this->street, trim(($this->postalCode ?? '').' '.($this->city ?? ''))]))); }
    public function getReferringPhysician(): ?Practitioner { return $this->referringPhysician; }
    public function setReferringPhysician(?Practitioner $practitioner): self { $this->referringPhysician = $practitioner; return $this; }
    public function isSharingOpposition(): bool { return $this->sharingOpposition; }
    public function setSharingOpposition(bool $opposition): self { $this->sharingOpposition = $opposition; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getErasedAt(): ?\DateTimeImmutable { return $this->erasedAt; }
    public function erase(): self
    {
        $this->familyName = '';
        $this->givenNames = '';
        $this->birthDate = null;
        $this->phone = $this->street = $this->postalCode = $this->city = null;
        $this->referringPhysician = null;
        $this->erasedAt = new \DateTimeImmutable();

        return $this;
    }
}
