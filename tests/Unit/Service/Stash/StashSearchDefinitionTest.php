<?php

namespace App\Tests\Unit\Service\Stash;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Entity\Stash;
use App\Service\Stash\StashSearchDefinition;
use PHPUnit\Framework\TestCase;

class StashSearchDefinitionTest extends TestCase
{
    private StashSearchDefinition $definition;

    protected function setUp(): void
    {
        $this->definition = new StashSearchDefinition();
    }

    public function testGetEntityClassReturnsStashClass(): void
    {
        $this->assertEquals(Stash::class, $this->definition->getEntityClass());
    }

    public function testConfigureAddsSortablePinned(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('pinned'));
    }

    public function testConfigureAddsSortableExpiresAt(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('expiresAt'));
    }

    public function testConfigureAddsSortableUpdatedAt(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('updatedAt'));
    }

    public function testConfigureDisallowsUnknownSortFields(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertFalse($configurator->isSortAllowed('id'));
        $this->assertFalse($configurator->isSortAllowed('createdAt'));
        $this->assertFalse($configurator->isSortAllowed('type'));
    }

    public function testConfigureHasNoFilters(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertFalse($configurator->isFilterAllowed('expired'));
        $this->assertFalse($configurator->isFilterAllowed('pinned'));
        $this->assertFalse($configurator->isFilterAllowed('active'));
    }
}
