<?php

namespace Base\Health\Entity;

use Base\Health\Enum\Sector;
use Base\Health\Repository\PractitionerRepository;
use Base\Office\Entity\Member;
use Doctrine\ORM\Mapping as ORM;

/**
 * What a member of the team is as a health professional: the profession
 * (the Annuaire Santé's code and its name), a specialty, the agreement with
 * the health insurance, whether the Carte Vitale and third-party payment are
 * taken, the MSSanté address. The RPPS number is the member's registryId:
 * "Lire l'Annuaire Santé" fills the profession and the MSSanté address.
 */
#[ORM\Entity(repositoryClass: PractitionerRepository::class)]
#[ORM\Table(name: 'health_practitioner')]
class Practitioner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\OneToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Member $member = null;

    /** TRE_G15: 10 médecin, 60 infirmier, 70 masseur-kinésithérapeute, 50 sage-femme... */
    #[ORM\Column(length: 8, nullable: true)]
    protected ?string $professionCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $profession = null;

    #[ORM\Column(length: 160, nullable: true)]
    protected ?string $specialty = null;

    #[ORM\Column(length: 24, enumType: Sector::class, nullable: true)]
    protected ?Sector $sector = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $carteVitale = true;

    #[ORM\Column(type: 'boolean')]
    protected bool $thirdPartyPayment = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $acceptsNewPatients = true;

    #[ORM\Column(length: 180, nullable: true)]
    protected ?string $mssante = null;

    public function __construct(?Member $member = null, ?string $profession = null, ?string $professionCode = null)
    {
        $this->member = $member;
        $this->profession = $profession;
        $this->professionCode = $professionCode;
    }

    public function __toString(): string
    {
        return (string) $this->member;
    }

    public function getId(): ?int { return $this->id; }
    public function getMember(): ?Member { return $this->member; }
    public function setMember(?Member $member): self { $this->member = $member; return $this; }
    public function getRpps(): ?string { return $this->member?->getRegistryId(); }
    public function getProfessionCode(): ?string { return $this->professionCode; }
    public function setProfessionCode(?string $code): self { $this->professionCode = $code ?: null; return $this; }
    public function getProfession(): ?string { return $this->profession; }
    public function setProfession(?string $profession): self { $this->profession = $profession ?: null; return $this; }
    public function getSpecialty(): ?string { return $this->specialty; }
    public function setSpecialty(?string $specialty): self { $this->specialty = $specialty ?: null; return $this; }
    public function getSector(): ?Sector { return $this->sector; }
    public function setSector(Sector|string|null $sector): self { $this->sector = \is_string($sector) ? Sector::tryFrom($sector) : $sector; return $this; }
    public function getSectorValue(): ?string { return $this->sector?->value; }
    public function setSectorValue(?string $value): self { return $this->setSector($value); }
    public function isCarteVitale(): bool { return $this->carteVitale; }
    public function setCarteVitale(bool $on): self { $this->carteVitale = $on; return $this; }
    public function isThirdPartyPayment(): bool { return $this->thirdPartyPayment; }
    public function setThirdPartyPayment(bool $on): self { $this->thirdPartyPayment = $on; return $this; }
    public function isAcceptsNewPatients(): bool { return $this->acceptsNewPatients; }
    public function setAcceptsNewPatients(bool $on): self { $this->acceptsNewPatients = $on; return $this; }
    public function getMssante(): ?string { return $this->mssante; }
    public function setMssante(?string $address): self { $this->mssante = $address ?: null; return $this; }

    /** A physician (the only profession a patient declares as "médecin traitant"). */
    public function isPhysician(): bool
    {
        return '10' === $this->professionCode;
    }
}
