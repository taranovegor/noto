<?php

namespace App\Tests\Unit\Service\Stash;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Context\DoctrineFilterContext;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Stash;
use App\Service\Stash\StashSearchDefinition;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
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

    public function testConfigureAddsActiveFilter(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isFilterAllowed('active'));
        $this->assertNotNull($configurator->getFilterDefinition('active'));
    }

    public function testConfigureAllowsActiveFilterWithEqOperatorOnly(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $operators = $configurator->getFilterDefinition('active')->getOperators();

        $this->assertCount(1, $operators);
        $this->assertContains(FilterOperator::Eq, $operators);
    }

    public function testConfigureActiveFilterHasHandler(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertNotNull($configurator->getFilterDefinition('active')->getHandler());
    }

    public function testConfigureAddsSortableCreatedAt(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('createdAt'));
    }

    public function testConfigureAddsSortableUpdatedAt(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('updatedAt'));
    }

    public function testConfigureDisallowsUnknownFilters(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertFalse($configurator->isFilterAllowed('pinned'));
        $this->assertFalse($configurator->isFilterAllowed('expiresAt'));
        $this->assertFalse($configurator->isFilterAllowed('type'));
    }

    public function testConfigureDisallowsUnknownSortFields(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);

        $this->assertTrue($configurator->isSortAllowed('pinned'));
        $this->assertFalse($configurator->isSortAllowed('id'));
        $this->assertFalse($configurator->isSortAllowed('expiresAt'));
    }

    public function testActiveHandlerWithTrueAddsOrCondition(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);
        $handler = $configurator->getFilterDefinition('active')->getHandler();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn(new Expr());
        $qb->expects($this->once())
            ->method('andWhere')
            ->with($this->isInstanceOf(Expr\Orx::class))
            ->willReturn($qb);
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturn($qb);

        $context = new DoctrineFilterContext('s', $qb);
        ($handler)($context, FilterOperator::Eq, true);
    }

    public function testActiveHandlerWithFalseAddsAndConditions(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);
        $handler = $configurator->getFilterDefinition('active')->getHandler();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn(new Expr());
        $qb->expects($this->once())
            ->method('andWhere')
            ->with(
                $this->isInstanceOf(Expr\Comparison::class),
                $this->isInstanceOf(Expr\Comparison::class),
            )
            ->willReturn($qb);
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturn($qb);

        $context = new DoctrineFilterContext('s', $qb);
        ($handler)($context, FilterOperator::Eq, false);
    }

    public function testActiveHandlerWithTrueSetsParameters(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);
        $handler = $configurator->getFilterDefinition('active')->getHandler();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn(new Expr());
        $qb->method('andWhere')->willReturn($qb);

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with(
                $this->logicalOr(
                    $this->equalTo('pinned'),
                    $this->equalTo('now'),
                ),
                $this->logicalOr(
                    $this->isTrue(),
                    $this->isInstanceOf(\DateTime::class),
                ),
            )
            ->willReturn($qb);

        $context = new DoctrineFilterContext('s', $qb);
        ($handler)($context, FilterOperator::Eq, true);
    }

    public function testActiveHandlerWithFalseSetsParameters(): void
    {
        $configurator = new SearchConfigurator();
        $this->definition->configure($configurator);
        $handler = $configurator->getFilterDefinition('active')->getHandler();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn(new Expr());
        $qb->method('andWhere')->willReturn($qb);

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with(
                $this->logicalOr(
                    $this->equalTo('pinned'),
                    $this->equalTo('now'),
                ),
                $this->logicalOr(
                    $this->isTrue(),
                    $this->isInstanceOf(\DateTime::class),
                ),
            )
            ->willReturn($qb);

        $context = new DoctrineFilterContext('s', $qb);
        ($handler)($context, FilterOperator::Eq, false);
    }
}
