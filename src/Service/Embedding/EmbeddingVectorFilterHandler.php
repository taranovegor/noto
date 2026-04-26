<?php

namespace App\Service\Embedding;

use App\Component\Searcher\Context\DoctrineFilterContext;
use App\Component\Searcher\Context\FilterContextInterface;
use App\Component\Searcher\Definition\FilterHandlerInterface;
use App\Component\Searcher\Enum\OperatorInterface;
use App\Entity\Embedding;

/**
 * Handles vector similarity filtering for Ref entities by joining with Embedding records.
 * Filters referenced objects based on vector distance to a provided embedding vector.
 *
 * @see Embedding
 */
final readonly class EmbeddingVectorFilterHandler implements FilterHandlerInterface
{
    public function __invoke(FilterContextInterface $context, OperatorInterface $operator, mixed $value): void
    {
        if (!$context instanceof DoctrineFilterContext) {
            throw new \InvalidArgumentException(sprintf('Filter context must be %s.', DoctrineFilterContext::class));
        }

        if (!is_array($value) || empty($value)) {
            return;
        }

        $rootAlias = $context->getRootAlias();
        $paramName = 'embedding_vector';

        $context->join(
            Embedding::class,
            'emb',
            'WITH',
            sprintf('emb.parent = %s', $rootAlias),
        )
            ->addOrderBy(sprintf('distance(emb.vector, :%s)', $paramName), 'ASC')
            ->setParameter($paramName, $value, 'vector');
    }
}
