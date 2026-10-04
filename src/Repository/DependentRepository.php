<?php

namespace Base\Health\Repository;

use Base\Health\Entity\Dependent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Dependent> */
class DependentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dependent::class);
    }

    /** @return list<Dependent> */
    public function findForHolder(object $holder): array
    {
        return $this->findBy(['holder' => $holder], ['givenName' => 'ASC']);
    }
}
