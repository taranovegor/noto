<?php

namespace App\Repository;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    /**
     * @return Attachment[]
     */
    public function findOwnedBy(Ref $source): array
    {
        return $this->createQueryBuilder('a')
            ->join(Link::class, 'l', 'WITH', 'l.target = a.ref AND l.source = :source AND l.kind = :kind')
            ->setParameter('source', $source)
            ->setParameter('kind', LinkKind::Ownership)
            ->getQuery()
            ->getResult();
    }
}
