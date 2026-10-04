<?php

namespace Base\Health\Entity;

use Base\Health\Repository\FeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A fee as it must be displayed (French law has practitioners post their
 * fees and the reimbursement base): what for, how much, what the health
 * insurance refunds on, for one practitioner or for the whole practice.
 */
#[ORM\Entity(repositoryClass: FeeRepository::class)]
#[ORM\Table(name: 'health_fee')]
class Fee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Practitioner::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?Practitioner $practitioner = null;

    /** For a fee of the whole practice: the group it is shown under ("Soins infirmiers"). */
    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $category = null;

    #[ORM\Column(length: 200)]
    protected string $label = '';

    /** As printed: "30 €", "de 30 à 55 €". */
    #[ORM\Column(length: 80)]
    protected string $amount = '';

    /** The reimbursement base: "30 €", or a note ("pris en charge à 60 % sur prescription"). */
    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $reimbursement = null;

    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    public function __construct(string $label = '', string $amount = '', ?Practitioner $practitioner = null, ?string $reimbursement = null, ?string $category = null)
    {
        $this->label = $label;
        $this->amount = $amount;
        $this->practitioner = $practitioner;
        $this->reimbursement = $reimbursement;
        $this->category = $category;
    }

    public function __toString(): string
    {
        return $this->label;
    }

    public function getId(): ?int { return $this->id; }
    public function getPractitioner(): ?Practitioner { return $this->practitioner; }
    public function setPractitioner(?Practitioner $practitioner): self { $this->practitioner = $practitioner; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category ?: null; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = trim((string) $label); return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(?string $amount): self { $this->amount = trim((string) $amount); return $this; }
    public function getReimbursement(): ?string { return $this->reimbursement; }
    public function setReimbursement(?string $text): self { $this->reimbursement = $text ?: null; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): self { $this->position = (int) $position; return $this; }
}
