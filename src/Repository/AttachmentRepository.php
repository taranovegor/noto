<?php

namespace App\Repository;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Enum\LinkKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Attachment>
 */
class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attachment::class);
    }

    public function add(Attachment $attachment): void
    {
        $this->getEntityManager()->persist($attachment);
    }

    public function remove(Attachment $attachment): void
    {
        $this->getEntityManager()->remove($attachment);
    }

    /**
     * @return Attachment[]
     */
    public function findDangling(\DateTimeImmutable $olderThan): array
    {
        $ownedIds = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(l.target)')
            ->from(Link::class, 'l')
            ->where('l.kind = :kind')
            ->getDQL();

        return $this->createQueryBuilder('a')
            ->where('a.createdAt < :cutoff')
            ->andWhere('a.id NOT IN ('.$ownedIds.')')
            ->setParameter('cutoff', $olderThan)
            ->setParameter('kind', LinkKind::Ownership)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Uuid[] $ids
     *
     * @return Attachment[]
     */
    public function findByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
