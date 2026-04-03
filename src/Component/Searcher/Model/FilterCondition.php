<?php

namespace App\Component\Searcher\Model;

use App\Component\Searcher\Enum\OperatorInterface;

final readonly class FilterCondition
{
    public function __construct(
        public string $name,
        public OperatorInterface $operator,
        public mixed $value,
    ) {
    }
}
