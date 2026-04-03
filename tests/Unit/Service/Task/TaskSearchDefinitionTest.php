<?php

namespace App\Tests\Unit\Service\Task;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Task;
use App\Service\Task\TaskSearchDefinition;
use PHPUnit\Framework\TestCase;

class TaskSearchDefinitionTest extends TestCase
{
    private TaskSearchDefinition $definition;

    protected function setUp(): void
    {
        $this->definition = new TaskSearchDefinition();
    }

    public function testGetEntityClassReturnsTaskClass(): void
    {
        $this->assertEquals(Task::class, $this->definition->getEntityClass());
    }

    public function testConfigureAddsStatusFilter(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isFilterAllowed('status'));
        $definition = $configurator->getFilterDefinition('status');
        $this->assertNotNull($definition);
    }

    public function testConfigureAllowsStatusFilterWithEqAndInOperators(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $filterDef = $configurator->getFilterDefinition('status');
        $operators = $filterDef->getOperators();

        $this->assertCount(2, $operators);
        $this->assertContains(FilterOperator::Eq, $operators);
        $this->assertContains(FilterOperator::In, $operators);
    }

    public function testConfigureAddsSortableCreatedAtField(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('created_at'));
        $definition = $configurator->getSortDefinition('created_at');
        $this->assertNotNull($definition);
    }

    public function testConfigureMapsSortCreatedAtToPropertyName(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $sortDef = $configurator->getSortDefinition('created_at');
        $this->assertEquals('createdAt', $sortDef->getProperty());
    }

    public function testConfigureAddsSortableId(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('id'));
    }

    public function testConfigureDisallowsUnknownFilters(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertFalse($configurator->isFilterAllowed('priority'));
        $this->assertFalse($configurator->isFilterAllowed('deadline'));
        $this->assertFalse($configurator->isFilterAllowed('name'));
    }

    public function testConfigureDisallowsUnknownSortFields(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertFalse($configurator->isSortAllowed('priority'));
        $this->assertFalse($configurator->isSortAllowed('updated_at'));
        $this->assertFalse($configurator->isSortAllowed('unknown_field'));
    }

    public function testConfigureMultipleTimes(): void
    {
        $configurator1 = new SearchConfigurator();
        $configurator2 = new SearchConfigurator();

        $this->definition->configure($configurator1);
        $this->definition->configure($configurator2);

        $this->assertTrue($configurator1->isFilterAllowed('status'));
        $this->assertTrue($configurator2->isFilterAllowed('status'));
    }
}
