<?php

namespace App\Service\Embedding;

use App\Entity\Embedding;
use App\Entity\Ref;
use App\Repository\EmbeddingRepository;
use App\Repository\RefRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Query\QueryInterface;
use Symfony\AI\Store\Query\VectorQuery;
use Symfony\AI\Store\StoreInterface;
use Symfony\Component\Uid\Uuid;

final readonly class EmbeddingStore implements StoreInterface
{
    public function __construct(
        private EmbeddingRepository $embeddingRepository,
        private RefRepository $refRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \RuntimeException
     *
     * @todo Optimize embedding replacement: instead of deleting all embeddings for a Ref,
     *       implement diff-based replacement once Symfony AI bundle stabilizes.
     *       This would preserve unchanged embeddings and only update modified ones.
     */
    public function add(VectorDocument|array $documents): void
    {
        $documents = is_array($documents) ? $documents : [$documents];

        foreach ($documents as $document) {
            $this->validateDocument($document);
        }

        $grouped = $this->groupDocumentsByParent($documents);
        $missingRefs = [];

        $this->entityManager->beginTransaction();
        try {
            foreach ($grouped as $parentId => $docs) {
                $parent = $this->refRepository->find($parentId);
                if (null === $parent) {
                    $missingRefs[] = $parentId;
                    continue;
                }

                $this->embeddingRepository->createQueryBuilder('e')
                    ->delete(Embedding::class, 'e')
                    ->where('e.parent = :parent')
                    ->setParameter('parent', $parent)
                    ->getQuery()
                    ->execute();

                foreach ($docs as $document) {
                    $this->embeddingRepository->add($this->createEmbedding($document, $parent));
                }

                $this->entityManager->flush();
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        if ($missingRefs) {
            throw new \RuntimeException(sprintf('Unable to save Embedding for documents with Ref %s', implode(',', $missingRefs)));
        }
    }

    public function remove(array|string $ids, array $options = []): void
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $qb = $this->embeddingRepository->createQueryBuilder('e')
            ->delete(Embedding::class, 'e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids);

        if (isset($options['where'])) {
            $qb->andWhere($options['where']);

            if (isset($options['params'])) {
                foreach ($options['params'] as $paramName => $paramValue) {
                    $qb->setParameter($paramName, $paramValue);
                }
            }
        }

        $qb->getQuery()->execute();
    }

    public function query(QueryInterface $query, array $options = []): iterable
    {
        if (!$query instanceof VectorQuery) {
            throw new \InvalidArgumentException(sprintf('Query must be an instance of %s', VectorQuery::class));
        }

        $qb = $this->embeddingRepository->createQueryBuilder('e');

        $qb->select('e.id', 'e.vector', 'e.metadata', 'p.id as parent_id', 'distance(e.vector, :vector) AS score')
            ->join('e.parent', 'p')
            ->setParameter('vector', $query->getVector()->getData(), 'vector')
            ->orderBy('score', 'ASC')
            ->setMaxResults($options['limit'] ?? 5);

        if (isset($options['maxScore'])) {
            $qb->andWhere('distance(e.vector, :vector) <= :maxScore')
                ->setParameter('maxScore', $options['maxScore']);
        }

        if (isset($options['where'])) {
            $qb->andWhere($options['where']);

            if (isset($options['params'])) {
                foreach ($options['params'] as $paramName => $paramValue) {
                    $qb->setParameter($paramName, $paramValue);
                }
            }
        }

        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        foreach ($results as $row) {
            $metadata = array_merge($row['metadata'], [
                Metadata::KEY_PARENT_ID => (string) $row['parent_id'],
            ]);

            yield new VectorDocument(
                (string) $row['id'],
                new Vector($row['vector']),
                new Metadata($metadata),
                (float) $row['score'],
            );
        }
    }

    public function supports(string $queryClass): bool
    {
        return \in_array($queryClass, [
            VectorQuery::class,
        ], true);
    }

    private function validateDocument(VectorDocument $document): void
    {
        $docId = $document->getId();
        if (!self::isValidUuid($docId)) {
            throw new \RuntimeException(sprintf('Invalid document id "%s", uuid expected', $docId));
        }

        if (!$document->getMetadata()->hasParentId()) {
            throw new \RuntimeException(sprintf('Parent id must be set for document id "%s".', $docId));
        }

        $refId = $document->getMetadata()->getParentId();

        if (!self::isValidUuid($refId)) {
            throw new \RuntimeException(sprintf('Invalid parent id "%s", uuid expected', $refId));
        }
    }

    /**
     * @param array<int, VectorDocument> $documents
     *
     * @return array<string, array<int, VectorDocument>>
     */
    private function groupDocumentsByParent(array $documents): array
    {
        $grouped = [];
        foreach ($documents as $document) {
            $parentId = $document->getMetadata()->getParentId();

            if (!isset($grouped[$parentId])) {
                $grouped[$parentId] = [];
            }
            $grouped[$parentId][] = $document;
        }

        return $grouped;
    }

    private function createEmbedding(VectorDocument $document, Ref $parent): Embedding
    {
        $metadata = $document->getMetadata()->getArrayCopy();
        unset($metadata[Metadata::KEY_PARENT_ID]);

        return new Embedding(
            Uuid::fromString($document->getId()),
            $parent,
            $document->getVector()->getData(),
            $metadata,
        );
    }

    private static function isValidUuid(int|string $value): bool
    {
        return is_string($value) && Uuid::isValid($value);
    }
}
