<?php

namespace App\Component\Ai\Store\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Indexable
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        public string $identifierField,
        public array $fields,
    ) {
    }
}
