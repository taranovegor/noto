<?php

namespace App\Component\Searcher\Model;

final readonly class PaginationDetails
{
    public function __construct(
        public int $limit,
        public int $offset = 0,
    ) {
    }

    public static function unlimited(): self
    {
        return new self(0, 0);
    }
}
