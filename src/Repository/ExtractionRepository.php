<?php

namespace App\Repository;

use App\Entity\Extraction;
use App\Enum\ExtractionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Extraction>
 */
class ExtractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Extraction::class);
    }

    public function add(Extraction $extraction): void
    {
        $this->getEntityManager()->persist($extraction);
    }

    /**
     * @return Extraction[]
     */
    public function findStaleProcessing(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.updatedAt < :since')
            ->setParameter('status', ExtractionStatus::Processing)
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }
}
