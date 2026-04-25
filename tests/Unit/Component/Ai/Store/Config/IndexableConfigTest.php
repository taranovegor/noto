<?php

namespace App\Tests\Unit\Component\Ai\Store\Config;

use App\Component\Ai\Store\Config\IndexableConfig;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;

class IndexableConfigTest extends TestCase
{
    private IndexableConfig $config;

    protected function setUp(): void
    {
        $this->config = new IndexableConfig([
            Task::class => ['fields' => ['name', 'note'], 'identifierField' => 'id'],
        ]);
    }

    public function testHasReturnsTrueForKnownClass(): void
    {
        $this->assertTrue($this->config->has(Task::class));
    }

    public function testHasReturnsFalseForUnknownClass(): void
    {
        $this->assertFalse($this->config->has('App\Entity\Unknown'));
    }

    public function testFieldsReturnsConfiguredFields(): void
    {
        $this->assertSame(['name', 'note'], $this->config->fields(Task::class));
    }

    public function testFieldsThrowsForUnknownClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->config->fields('App\Entity\Unknown');
    }

    public function testClassesReturnsAllEntityClasses(): void
    {
        $this->assertSame([Task::class], $this->config->classes());
    }

    public function testEmptyConfigReturnsEmptyClasses(): void
    {
        $config = new IndexableConfig([]);

        $this->assertSame([], $config->classes());
    }
}
