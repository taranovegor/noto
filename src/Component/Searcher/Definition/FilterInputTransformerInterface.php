<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Enum\OperatorInterface;

interface FilterInputTransformerInterface
{
    public function __invoke(OperatorInterface $operator, mixed $value): mixed;
}
