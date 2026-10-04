<?php

namespace Base\Health\Repository;

use Base\Health\Entity\Practitioner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Practitioner> */
class PractitionerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Practitioner::class);
    }

    public function findOneByMember(?object $member): ?Practitioner
    {
        return null === $member ? null : $this->findOneBy(['member' => $member]);
    }

    public function findOneByUser(?object $user): ?Practitioner
    {
        return null === $user ? null : $this->createQueryBuilder('p')->innerJoin('p.member', 'm')->andWhere('m.user = :user')->setParameter('user', $user)->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /** By RPPS number: how Pro Santé Connect names a practitioner. */
    public function findOneByRpps(string $rpps): ?Practitioner
    {
        return $this->createQueryBuilder('p')->innerJoin('p.member', 'm')->andWhere('m.registryId = :rpps')->setParameter('rpps', $rpps)->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /** @return list<Practitioner> the practice's physicians, for "médecin traitant" */
    public function findPhysicians(): array
    {
        return $this->createQueryBuilder('p')->innerJoin('p.member', 'm')->andWhere("p.professionCode = '10'")->andWhere('m.active = true')->orderBy('m.position', 'ASC')->getQuery()->getResult();
    }
}
