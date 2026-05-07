<?php

namespace App\Repository;

use App\Entity\Stash;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stash>
 */
class StashRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stash::class);
    }

    public function add(Stash $stash): void
    {
        $this->getEntityManager()->persist($stash);
    }

    public function remove(Stash $stash): void
    {
        $this->getEntityManager()->remove($stash);
    }

    /**
     * @return Stash[]
     */
    public function findExpired(\DateTimeImmutable $cutoff): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.pinned = false')
            ->andWhere('s.expiresAt IS NOT NULL')
            ->andWhere('s.expiresAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }
}
