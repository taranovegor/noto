<?php

namespace App\Component\Broadcaster\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Broadcastable
{
    public function __construct(
        public string $namespace,
    ) {
    }
}
