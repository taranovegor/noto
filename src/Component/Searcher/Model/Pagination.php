<?php

namespace App\Component\Searcher\Model;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Pagination metadata for search results.
 */
final class Pagination
{
    public function __construct(
        #[Groups('pagination')]
        private readonly int $limit,
        #[Groups('pagination')]
        private readonly int $offset,
        #[Groups('pagination')]
        private readonly int $total,
    ) {
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
