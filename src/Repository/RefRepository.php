<?php

namespace App\Repository;

use App\Entity\Ref;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Ref>
 */
class RefRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ref::class);
    }

    /**
     * @param Uuid[] $ids
     *
     * @return Ref[]
     */
    public function findByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', array_map(strval(...), $ids))
            ->getQuery()
            ->getResult();
    }
}
