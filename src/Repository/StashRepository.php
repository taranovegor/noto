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
}
