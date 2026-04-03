<?php

namespace App\Tests\Unit\Component\Searcher\Loader;

use App\Component\Searcher\Loader\SearchDefinitionLoader;
use App\Dto\Task\SearchTaskDto;
use App\Service\Task\TaskSearchDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SearchDefinitionLoaderTest extends TestCase
{
    private SearchDefinitionLoader $loader;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);
        $this->loader = new SearchDefinitionLoader($this->container);
    }

    public function testLoadReturnsDefinitionFromAttribute(): void
    {
        $definition = new TaskSearchDefinition();

        $this->container->method('has')->willReturnMap([
            [TaskSearchDefinition::class, true],
        ]);

        $this->container->method('get')->willReturnMap([
            [TaskSearchDefinition::class, $definition],
        ]);

        $result = $this->loader->load(SearchTaskDto::class);

        $this->assertSame($definition, $result);
    }

    public function testLoadThrowsIfSearchableAttributeMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must have #[Searchable] attribute');

        $this->loader->load(\stdClass::class);
    }

    public function testLoadThrowsIfDefinitionNotInContainer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found in service container');

        $this->container->method('has')->willReturnMap([
            [TaskSearchDefinition::class, false],
        ]);

        $this->loader->load(SearchTaskDto::class);
    }

    public function testLoadThrowsIfDefinitionDoesNotImplementInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must implement SearchableDefinitionInterface');

        $this->container->method('has')->willReturnMap([
            [TaskSearchDefinition::class, true],
        ]);

        $this->container->method('get')->willReturnMap([
            [TaskSearchDefinition::class, new \stdClass()],
        ]);

        $this->loader->load(SearchTaskDto::class);
    }
}
