<?php

namespace App\Service\Embedding;

use App\Component\Searcher\Definition\FilterInputTransformerInterface;
use App\Component\Searcher\Enum\OperatorInterface;
use Symfony\AI\Store\Document\VectorizerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Transforms text filter input into vector embeddings for semantic search.
 *
 * Converts user-provided text queries into numerical vectors using an embedding model,
 * enabling similarity-based filtering and ordering. Results are cached for 1 hour to avoid
 * redundant API calls for identical search queries.
 */
final readonly class FilterInputVectorizer implements FilterInputTransformerInterface
{
    public function __construct(
        private VectorizerInterface $vectorizer,
        private CacheInterface $cache,
        private \DateInterval $ttl = new \DateInterval('PT1H'),
    ) {
    }

    /**
     * @return array<float>
     */
    public function __invoke(OperatorInterface $operator, mixed $value): array
    {
        if (!is_string($value)) {
            return [];
        }

        $cacheKey = $this->generateCacheKey($value);

        return $this->cache->get($cacheKey, function (ItemInterface $item, bool $isHit) use ($value): array {
            if (!$isHit) {
                $item->expiresAfter($this->ttl);
            }

            return $this->vectorizer->vectorize($value)->getData();
        });
    }

    private function generateCacheKey(string $value): string
    {
        return 'embedding_vector_'.hash('sha256', $value);
    }
}
