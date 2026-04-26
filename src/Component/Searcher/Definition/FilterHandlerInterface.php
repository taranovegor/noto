<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Context\FilterContextInterface;
use App\Component\Searcher\Enum\OperatorInterface;

interface FilterHandlerInterface
{
    public function __invoke(FilterContextInterface $context, OperatorInterface $operator, mixed $value): void;
}
