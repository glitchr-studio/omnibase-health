<?php

namespace Base\Health\Repository;

use Base\Health\Entity\PatientProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PatientProfile> */
class PatientProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientProfile::class);
    }

    public function findOneByUser(?object $user): ?PatientProfile
    {
        return null === $user ? null : $this->findOneBy(['user' => $user]);
    }

    /** @return list<PatientProfile> by name, for the staff's search */
    public function search(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.familyName) LIKE :q OR LOWER(p.givenNames) LIKE :q')->setParameter('q', '%'.mb_strtolower(trim($query)).'%')
            ->andWhere('p.erasedAt IS NULL')
            ->orderBy('p.familyName', 'ASC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
