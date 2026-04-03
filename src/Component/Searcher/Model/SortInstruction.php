<?php

namespace App\Component\Searcher\Model;

use App\Component\Searcher\Enum\SortDirection;

final readonly class SortInstruction
{
    public function __construct(
        public string $name,
        public SortDirection $direction,
    ) {
    }
}
