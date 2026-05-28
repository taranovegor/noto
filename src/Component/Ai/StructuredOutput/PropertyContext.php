<?php

namespace App\Component\Ai\StructuredOutput;

use App\Component\Ai\StructuredOutput\Attribute\Schema;

/**
 * @internal
 */
final readonly class PropertyContext
{
    /**
     * @param class-string       $className
     * @param list<class-string> $visited
     */
    public function __construct(
        public string $className,
        public string $name,
        public bool $allowsNull,
        public ?Schema $attr,
        public array $visited,
    ) {
    }
}
