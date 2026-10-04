<?php

namespace Base\Health\Repository;

use Base\Health\Entity\IdentityLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<IdentityLink> */
class IdentityLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdentityLink::class);
    }

    public function findOneBySub(string $provider, string $sub): ?IdentityLink
    {
        return $this->findOneBy(['provider' => $provider, 'sub' => $sub]);
    }

    /** @return list<IdentityLink> */
    public function findForUser(object $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'ASC']);
    }
}
