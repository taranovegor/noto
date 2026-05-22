<?php

namespace App\Tests\Unit\Component\Broadcaster\Config;

use App\Component\Broadcaster\Config\BroadcastableConfig;
use PHPUnit\Framework\TestCase;

class BroadcastableConfigTest extends TestCase
{
    public function testGetNamespaceReturnsNamespaceForKnownClass(): void
    {
        $config = new BroadcastableConfig([\stdClass::class => 'notes']);

        $this->assertSame('notes', $config->getNamespace(\stdClass::class));
    }

    public function testGetNamespaceReturnsNullForUnknownClass(): void
    {
        $config = new BroadcastableConfig([]);

        $this->assertNull($config->getNamespace(\stdClass::class));
    }

    public function testClassesReturnsAllRegisteredClasses(): void
    {
        $map = [
            'App\Entity\Memo' => 'memos',
            'App\Entity\Task' => 'tasks',
        ];

        $config = new BroadcastableConfig($map);

        $this->assertSame(['App\Entity\Memo', 'App\Entity\Task'], $config->classes());
    }

    public function testEmptyConfigReturnsEmptyClasses(): void
    {
        $config = new BroadcastableConfig();

        $this->assertSame([], $config->classes());
    }
}
