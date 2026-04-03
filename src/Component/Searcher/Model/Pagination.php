<?php

namespace App\Component\Searcher\Model;

/**
 * Pagination metadata for search results.
 */
final class Pagination
{
    public function __construct(
        private readonly int $limit,
        private readonly int $offset,
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
