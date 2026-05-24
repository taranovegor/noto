<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Enum\OperatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface FilterInputTransformerInterface
{
    public function __invoke(OperatorInterface $operator, mixed $value): mixed;
}
