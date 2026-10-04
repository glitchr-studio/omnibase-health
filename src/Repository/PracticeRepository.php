<?php

namespace Base\Health\Repository;

use Base\Health\Entity\Practice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Practice> */
class PracticeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Practice::class);
    }

    /** The practice of the main office (a site is one practice). */
    public function findMain(): ?Practice
    {
        return $this->createQueryBuilder('p')->innerJoin('p.office', 'o')->orderBy('o.main', 'DESC')->addOrderBy('o.position', 'ASC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
