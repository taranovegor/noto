<?php

namespace App\Component\Ai\Store\Document;

use App\Component\Ai\Store\Config\IndexableConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\AI\Store\Document\LoaderInterface;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Uid\Uuid;

final readonly class IndexableEntityLoader implements LoaderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TextDocumentFactory $factory,
        private IndexableConfig $config,
    ) {
    }

    public function load(?string $source = null, array $options = []): iterable
    {
        if (null === $source) {
            yield from $this->loadAll();

            return;
        }

        yield from $this->loadById(IndexableReference::fromString($source));
    }

    /**
     * @return iterable<TextDocument>
     */
    private function loadAll(): iterable
    {
        foreach ($this->config->classes() as $entityClass) {
            $repository = $this->entityManager->getRepository($entityClass);
            $entities = $repository->createQueryBuilder('e')->getQuery()->getResult();

            yield from $this->createDocuments($entities);
        }
    }

    /**
     * @return iterable<TextDocument>
     */
    private function loadById(IndexableReference $reference): iterable
    {
        $entityClass = $reference->objectClass;

        if (!$this->config->has($entityClass)) {
            return;
        }

        $repository = $this->entityManager->getRepository($entityClass);

        $entities = $repository->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', Uuid::fromString($reference->objectId))
            ->getQuery()
            ->getResult();

        yield from $this->createDocuments($entities);
    }

    /**
     * @param array<object> $entities
     *
     * @return iterable<TextDocument>
     */
    private function createDocuments(array $entities): iterable
    {
        foreach ($entities as $entity) {
            try {
                yield $this->factory->create($entity);
            } catch (\InvalidArgumentException|\RuntimeException) {
                continue;
            }
        }
    }
}
