<?php

namespace App\Tests\Unit\Component\Broadcaster\Attribute;

use App\Component\Broadcaster\Attribute\Broadcastable;
use PHPUnit\Framework\TestCase;

class BroadcastableTest extends TestCase
{
    public function testAttributeStoresNamespace(): void
    {
        $attribute = new Broadcastable('notes');

        $this->assertSame('notes', $attribute->namespace);
    }

    public function testAttributeIsPhpAttribute(): void
    {
        $reflection = new \ReflectionClass(Broadcastable::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
    }

    public function testAttributeTargetsClass(): void
    {
        $reflection = new \ReflectionClass(Broadcastable::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        /** @var \Attribute $instance */
        $instance = $attributes[0]->newInstance();

        $this->assertSame(\Attribute::TARGET_CLASS, $instance->flags);
    }
}
