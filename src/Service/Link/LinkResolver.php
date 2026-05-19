<?php

namespace App\Service\Link;

use App\Entity\Link;
use App\Entity\Ref;
use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Service\ReferenceableRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

readonly class LinkResolver
{
    public function __construct(
        private ManagerRegistry $registry,
        private ReferenceableRegistry $referenceableRegistry,
    ) {
    }

    /**
     * @template T of ReferenceableInterface
     *
     * @param class-string<T>|null $target
     *
     * @return ($target is class-string<T> ? T[] : ReferenceableInterface[])
     */
    public function resolve(Ref $source, ?LinkKind $kind = null, ?string $target = null): array
    {
        if (null !== $target) {
            return $this->resolveTo($source, $kind, $target);
        }

        $criteria = ['source' => $source];
        if (null !== $kind) {
            $criteria['kind'] = $kind;
        }

        /** @var EntityRepository<Link> $linkRepo */
        $linkRepo = $this->registry->getRepository(Link::class);
        $links = $linkRepo->findBy($criteria);

        if (!$links) {
            return [];
        }

        $idsByType = [];
        foreach ($links as $link) {
            $idsByType[$link->targetType->value][] = $link->target->id;
        }

        $entities = [];
        foreach ($idsByType as $refType => $refIds) {
            if (!$this->referenceableRegistry->hasClass(RefType::from($refType))) {
                continue;
            }

            $class = $this->referenceableRegistry->getClass(RefType::from($refType));

            /** @var EntityRepository<ReferenceableInterface> $repo */
            $repo = $this->registry->getRepository($class);

            $found = $repo->createQueryBuilder('e')
                ->where('e.ref IN (:refs)')
                ->setParameter('refs', $refIds)
                ->getQuery()
                ->getResult();

            array_push($entities, ...$found);
        }

        return $entities;
    }

    /**
     * @template T of ReferenceableInterface
     *
     * @param class-string<T> $target
     *
     * @return T[]
     */
    private function resolveTo(Ref $sourceRef, ?LinkKind $kind, string $target): array
    {
        $repo = $this->registry->getRepository($target);

        if (!$repo instanceof EntityRepository) {
            throw new \LogicException(\sprintf('Repository for "%s" must extend EntityRepository.', $target));
        }

        $qb = $repo
            ->createQueryBuilder('e')
            ->join(Link::class, 'l', 'WITH', 'l.target = e.ref AND l.source = :source')
            ->setParameter('source', $sourceRef);

        if (null !== $kind) {
            $qb->andWhere('l.kind = :kind')
               ->setParameter('kind', $kind);
        }

        return $qb->getQuery()->getResult();
    }
}
