<?php

namespace App\Component\Searcher\Model;

final readonly class PaginationDetails
{
    /**
     * @param int|null $limit Page size, or null for no limit (unlimited)
     */
    public function __construct(
        public ?int $limit,
        public int $offset = 0,
    ) {
    }

    public static function unlimited(): self
    {
        return new self(null);
    }
}
