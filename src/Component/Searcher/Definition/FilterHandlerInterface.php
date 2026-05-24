<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Context\FilterContextInterface;
use App\Component\Searcher\Enum\OperatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface FilterHandlerInterface
{
    public function __invoke(FilterContextInterface $context, OperatorInterface $operator, mixed $value): void;
}
