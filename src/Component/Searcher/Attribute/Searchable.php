<?php

namespace App\Component\Searcher\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Searchable
{
    public function __construct(
        public string $definition,
    ) {
    }
}
