<?php

namespace App\Component\Ai\StructuredOutput\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Schema
{
    /**
     * @param ?string $description description of the property
     */
    public function __construct(
        public ?string $description = null,
    ) {
    }
}
