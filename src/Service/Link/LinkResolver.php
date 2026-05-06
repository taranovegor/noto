<?php

namespace App\Service\Link;

use App\Entity\Link;
use App\Entity\Ref;
use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

readonly class LinkResolver
{
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    /**
     * @template T of ReferenceableInterface
     *
     * @param class-string<T> $targetClass
     *
     * @return T[]
     */
    public function resolve(Ref|ReferenceableInterface $source, LinkKind $kind, string $targetClass): array
    {
        $sourceRef = $source instanceof Ref ? $source : $source->getRef();

        $repo = $this->registry->getRepository($targetClass);

        if (!$repo instanceof EntityRepository) {
            throw new \LogicException(\sprintf('Repository for "%s" must extend EntityRepository.', $targetClass));
        }

        return $repo
            ->createQueryBuilder('e')
            ->join(Link::class, 'l', 'WITH', 'l.target = e.ref AND l.source = :source AND l.kind = :kind')
            ->setParameter('source', $sourceRef)
            ->setParameter('kind', $kind)
            ->getQuery()
            ->getResult();
    }
}
