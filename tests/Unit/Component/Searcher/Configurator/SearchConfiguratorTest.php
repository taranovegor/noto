<?php

namespace App\Tests\Unit\Component\Searcher\Configurator;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Enum\FilterOperator;
use PHPUnit\Framework\TestCase;

class SearchConfiguratorTest extends TestCase
{
    private SearchConfigurator $configurator;

    protected function setUp(): void
    {
        $this->configurator = new SearchConfigurator();
    }

    public function testAddFilterReturnsFilterDefinition(): void
    {
        $definition = $this->configurator->addFilter('status', [FilterOperator::Eq]);

        $this->assertNotNull($definition);
        $this->assertEquals('status', $definition->getName());
    }

    public function testAddFilterMakesFieldFilterable(): void
    {
        $this->configurator->addFilter('status', [FilterOperator::Eq, FilterOperator::In]);

        $this->assertTrue($this->configurator->isFilterAllowed('status'));
        $this->assertFalse($this->configurator->isFilterAllowed('unknown'));
    }

    public function testAddSortableReturnsDefinition(): void
    {
        $definition = $this->configurator->addSortable('name');

        $this->assertNotNull($definition);
        $this->assertEquals('name', $definition->getName());
    }

    public function testAddSortableMakesFieldSortable(): void
    {
        $this->configurator->addSortable('createdAt');

        $this->assertTrue($this->configurator->isSortAllowed('createdAt'));
        $this->assertFalse($this->configurator->isSortAllowed('unknown'));
    }

    public function testGetFilterDefinitionReturnsDefinition(): void
    {
        $this->configurator->addFilter('status', [FilterOperator::Eq]);

        $definition = $this->configurator->getFilterDefinition('status');

        $this->assertNotNull($definition);
        $this->assertEquals('status', $definition->getName());
    }

    public function testGetFilterDefinitionReturnsNullForUnknownField(): void
    {
        $definition = $this->configurator->getFilterDefinition('unknown');

        $this->assertNull($definition);
    }

    public function testGetSortDefinitionReturnsDefinition(): void
    {
        $this->configurator->addSortable('deadline');

        $definition = $this->configurator->getSortDefinition('deadline');

        $this->assertNotNull($definition);
        $this->assertEquals('deadline', $definition->getName());
    }

    public function testGetSortDefinitionReturnsNullForUnknownField(): void
    {
        $definition = $this->configurator->getSortDefinition('unknown');

        $this->assertNull($definition);
    }

    public function testSetPaginationLimitsSetsMaxAndDefault(): void
    {
        $this->configurator->setPaginationLimits(50, 25);

        $this->assertEquals(50, $this->configurator->getMaxLimit());
        $this->assertEquals(25, $this->configurator->getDefaultLimit());
    }

    public function testSetPaginationLimitsReturnsThis(): void
    {
        $result = $this->configurator->setPaginationLimits(100, 20);

        $this->assertSame($this->configurator, $result);
    }

    public function testDefaultPaginationLimitsAreSet(): void
    {
        $this->assertEquals(100, $this->configurator->getMaxLimit());
        $this->assertEquals(20, $this->configurator->getDefaultLimit());
    }

    public function testGetFilterDefinitionsReturnsAll(): void
    {
        $this->configurator->addFilter('status', [FilterOperator::Eq]);
        $this->configurator->addFilter('priority', [FilterOperator::In]);

        $definitions = $this->configurator->getFilterDefinitions();

        $this->assertCount(2, $definitions);
        $this->assertArrayHasKey('status', $definitions);
        $this->assertArrayHasKey('priority', $definitions);
    }

    public function testGetSortDefinitionsReturnsAll(): void
    {
        $this->configurator->addSortable('createdAt');
        $this->configurator->addSortable('name');

        $definitions = $this->configurator->getSortDefinitions();

        $this->assertCount(2, $definitions);
        $this->assertArrayHasKey('createdAt', $definitions);
        $this->assertArrayHasKey('name', $definitions);
    }

    public function testMultipleFiltersAndSortablesCanBeAdded(): void
    {
        $this->configurator->addFilter('status', [FilterOperator::Eq, FilterOperator::In]);
        $this->configurator->addFilter('priority', [FilterOperator::Gte]);
        $this->configurator->addSortable('createdAt');
        $this->configurator->addSortable('deadline');

        $this->assertTrue($this->configurator->isFilterAllowed('status'));
        $this->assertTrue($this->configurator->isFilterAllowed('priority'));
        $this->assertTrue($this->configurator->isSortAllowed('createdAt'));
        $this->assertTrue($this->configurator->isSortAllowed('deadline'));
    }
}
