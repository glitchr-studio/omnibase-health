<?php

namespace Base\Health\Repository;

use Base\Health\Entity\Fee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Fee> */
class FeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fee::class);
    }

    /** @return list<Fee> */
    public function findForPractitioner(object $practitioner): array
    {
        return $this->findBy(['practitioner' => $practitioner], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Fee> every fee, the practice's first then each practitioner's */
    public function findOrdered(): array
    {
        return $this->createQueryBuilder('f')->leftJoin('f.practitioner', 'p')->leftJoin('p.member', 'm')->orderBy('m.position', 'ASC')->addOrderBy('f.position', 'ASC')->addOrderBy('f.id', 'ASC')->getQuery()->getResult();
    }
}
