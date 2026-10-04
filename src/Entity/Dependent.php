<?php

namespace Base\Health\Entity;

use App\Entity\User;
use Base\Health\Enum\Relation;
use Base\Health\Repository\DependentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Someone an account holder books for: a child, a parent they care for.
 * No account of their own; their appointments and documents are the
 * holder's to see.
 */
#[ORM\Entity(repositoryClass: DependentRepository::class)]
#[ORM\Table(name: 'health_dependent')]
class Dependent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?User $holder = null;

    #[ORM\Column(length: 120)]
    protected string $givenName = '';

    #[ORM\Column(length: 120)]
    protected string $familyName = '';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 16, enumType: Relation::class)]
    protected Relation $relation = Relation::CHILD;

    public function __construct(?User $holder = null, string $givenName = '', string $familyName = '', Relation $relation = Relation::CHILD)
    {
        $this->holder = $holder;
        $this->givenName = $givenName;
        $this->familyName = $familyName;
        $this->relation = $relation;
    }

    public function __toString(): string
    {
        return trim($this->givenName.' '.$this->familyName);
    }

    public function getId(): ?int { return $this->id; }
    public function getHolder(): ?User { return $this->holder; }
    public function setHolder(?User $holder): self { $this->holder = $holder; return $this; }
    public function getGivenName(): string { return $this->givenName; }
    public function setGivenName(?string $name): self { $this->givenName = trim((string) $name); return $this; }
    public function getFamilyName(): string { return $this->familyName; }
    public function setFamilyName(?string $name): self { $this->familyName = trim((string) $name); return $this; }
    public function getBirthDate(): ?\DateTimeImmutable { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $date): self { $this->birthDate = $date ? \DateTimeImmutable::createFromInterface($date) : null; return $this; }
    public function getRelation(): Relation { return $this->relation; }
    public function setRelation(Relation|string $relation): self { $this->relation = $relation instanceof Relation ? $relation : Relation::from($relation); return $this; }
    public function getRelationValue(): string { return $this->relation->value; }
    public function setRelationValue(?string $value): self { return $this->setRelation($value ?: Relation::CHILD->value); }
}
