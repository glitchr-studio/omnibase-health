<?php

namespace Base\Health\Entity;

use Base\Health\Enum\PracticeType;
use Base\Health\Repository\PracticeRepository;
use Base\Office\Entity\Office;
use Doctrine\ORM\Mapping as ORM;

/**
 * The practice of care an office is: its kind (a multi-professional
 * health centre, a nursing practice...), its FINESS number when it has
 * one, what to do in an emergency and how care is given out of hours.
 */
#[ORM\Entity(repositoryClass: PracticeRepository::class)]
#[ORM\Table(name: 'health_practice')]
class Practice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\OneToOne(targetEntity: Office::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Office $office = null;

    #[ORM\Column(length: 24, enumType: PracticeType::class)]
    protected PracticeType $type = PracticeType::MSP;

    #[ORM\Column(length: 9, nullable: true)]
    protected ?string $finess = null;

    /** A sentence for the home page: who the practice is. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $presentation = null;

    /** What the emergencies page says besides the numbers. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $emergencyInstructions = null;

    /** Evenings, weekends: who answers (permanence des soins). */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $outOfHours = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $acceptsNewPatients = true;

    #[ORM\Column(type: 'boolean')]
    protected bool $teleconsultation = true;

    #[ORM\Column(type: 'boolean')]
    protected bool $homeCare = true;

    public function __construct(?Office $office = null, PracticeType $type = PracticeType::MSP)
    {
        $this->office = $office;
        $this->type = $type;
    }

    public function __toString(): string
    {
        return (string) $this->office;
    }

    public function getId(): ?int { return $this->id; }
    public function getOffice(): ?Office { return $this->office; }
    public function setOffice(?Office $office): self { $this->office = $office; return $this; }
    public function getType(): PracticeType { return $this->type; }
    public function setType(PracticeType|string $type): self { $this->type = $type instanceof PracticeType ? $type : PracticeType::from($type); return $this; }
    public function getTypeValue(): string { return $this->type->value; }
    public function setTypeValue(?string $value): self { return $this->setType($value ?: PracticeType::MSP->value); }
    public function getFiness(): ?string { return $this->finess; }
    public function setFiness(?string $finess): self { $this->finess = $finess ? strtoupper((string) preg_replace('/\s+/', '', $finess)) : null; return $this; }
    public function getPresentation(): ?string { return $this->presentation; }
    public function setPresentation(?string $text): self { $this->presentation = $text ?: null; return $this; }
    public function getEmergencyInstructions(): ?string { return $this->emergencyInstructions; }
    public function setEmergencyInstructions(?string $text): self { $this->emergencyInstructions = $text ?: null; return $this; }
    public function getOutOfHours(): ?string { return $this->outOfHours; }
    public function setOutOfHours(?string $text): self { $this->outOfHours = $text ?: null; return $this; }
    public function isAcceptsNewPatients(): bool { return $this->acceptsNewPatients; }
    public function setAcceptsNewPatients(bool $accepts): self { $this->acceptsNewPatients = $accepts; return $this; }
    public function isTeleconsultation(): bool { return $this->teleconsultation; }
    public function setTeleconsultation(bool $on): self { $this->teleconsultation = $on; return $this; }
    public function isHomeCare(): bool { return $this->homeCare; }
    public function setHomeCare(bool $on): self { $this->homeCare = $on; return $this; }

    /** Home care first on the pages: a nursing practice, or any practice that only visits. */
    public function isHomeCareFirst(): bool
    {
        return $this->homeCare && $this->type->isHomeCareFirst();
    }
}
