<?php

namespace Base\Health\Repository;

use Base\Health\Entity\HomeCareRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<HomeCareRequest> */
class HomeCareRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeCareRequest::class);
    }

    /** @return list<HomeCareRequest> */
    public function findPending(int $limit = 20): array
    {
        return $this->findBy(['status' => \Base\Health\Enum\CareStatus::PENDING], ['createdAt' => 'ASC'], $limit);
    }

    public function countPending(): int
    {
        return $this->count(['status' => \Base\Health\Enum\CareStatus::PENDING]);
    }

    /** @return list<HomeCareRequest> */
    public function findForPatient(object $patient): array
    {
        return $this->findBy(['patient' => $patient], ['createdAt' => 'DESC']);
    }

    public function purgeDecidedBefore(\DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('r')->delete()->andWhere('r.decidedAt IS NOT NULL')->andWhere('r.decidedAt < :before')->setParameter('before', \Base\Database\Type\Utc::from($before))->getQuery()->execute();
    }
}
