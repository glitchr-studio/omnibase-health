<?php

namespace Base\Health\Entity;

use App\Entity\User;
use Base\Health\Repository\IdentityLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Whose account an identity of FranceConnect or Pro Santé Connect is: the
 * provider and its `sub` - the provider's own, stable identifier of the
 * person - never an e-mail address, which proves nothing about who someone
 * is. One identity, one account; an account may hold several identities.
 */
#[ORM\Entity(repositoryClass: IdentityLinkRepository::class)]
#[ORM\Table(name: 'health_identity_link')]
#[ORM\UniqueConstraint(name: 'health_identity_link_sub', columns: ['provider', 'sub'])]
class IdentityLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(length: 32)]
    protected string $provider;

    #[ORM\Column(length: 190)]
    protected string $sub;

    /** The level of assurance of the last sign-in (eidas1, eidas2...). */
    #[ORM\Column(length: 32, nullable: true)]
    protected ?string $acr = null;

    #[ORM\Column(type: 'utc_datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'utc_datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(User $user, string $provider, string $sub)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->sub = $sub;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getProvider(): string { return $this->provider; }
    public function getSub(): string { return $this->sub; }
    public function getAcr(): ?string { return $this->acr; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastUsedAt(): ?\DateTimeImmutable { return $this->lastUsedAt; }

    public function used(?string $acr = null): self
    {
        $this->lastUsedAt = new \DateTimeImmutable();
        $this->acr = $acr ?? $this->acr;

        return $this;
    }
}
