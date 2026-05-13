<?php

namespace App\Repository;

use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Link>
 */
class LinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Link::class);
    }

    public function add(Link $link): void
    {
        $this->getEntityManager()->persist($link);
    }

    public function remove(Link $link): void
    {
        $this->getEntityManager()->remove($link);
    }

    public function findLink(Ref $source, Ref $target, LinkKind $kind): ?Link
    {
        return $this->findOneBy([
            'source' => $source,
            'target' => $target,
            'kind' => $kind,
        ]);
    }

    public function hasOwnershipTarget(Uuid $targetId): bool
    {
        return (bool) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->join('l.target', 'r')
            ->where('r.id = :id')
            ->andWhere('l.kind = :kind')
            ->setParameter('id', $targetId)
            ->setParameter('kind', LinkKind::Ownership)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
