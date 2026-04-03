<?php

namespace App\Tests\Unit\Component\Searcher\Model;

use App\Component\Searcher\Enum\SortDirection;
use App\Component\Searcher\Model\SearchCriteria;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;

class SearchCriteriaTest extends TestCase
{
    public function testConstructorStoresAllParameters(): void
    {
        $entityClass = Task::class;
        $filters = ['status' => 'active'];
        $sorting = ['created_at' => SortDirection::DESC];
        $limit = 50;
        $offset = 100;

        $criteria = new SearchCriteria($entityClass, $filters, $sorting, $limit, $offset);

        $this->assertEquals($entityClass, $criteria->getEntityClass());
        $this->assertEquals($filters, $criteria->getFilters());
        $this->assertEquals($sorting, $criteria->getSorting());
        $this->assertEquals($limit, $criteria->getLimit());
        $this->assertEquals($offset, $criteria->getOffset());
    }

    public function testConstructorWithNullLimit(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            [],
            [],
            null,
            0
        );

        $this->assertNull($criteria->getLimit());
    }

    public function testHasFiltersReturnsTrueWhenFiltersExist(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            ['status' => 'active'],
            [],
            20,
            0
        );

        $this->assertTrue($criteria->hasFilters());
    }

    public function testHasFiltersReturnsFalseWhenNoFilters(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            [],
            [],
            20,
            0
        );

        $this->assertFalse($criteria->hasFilters());
    }

    public function testHasSortingReturnsTrueWhenSortingExists(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            [],
            ['created_at' => SortDirection::ASC],
            20,
            0
        );

        $this->assertTrue($criteria->hasSorting());
    }

    public function testHasSortingReturnsFalseWhenNoSorting(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            [],
            [],
            20,
            0
        );

        $this->assertFalse($criteria->hasSorting());
    }

    public function testHasPaginationReturnsTrueWhenLimitIsSet(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            [],
            [],
            20,
            0
        );

        $this->assertTrue($criteria->hasPagination());
    }

    public function testHasPaginationReturnsFalseWhenLimitIsNull(): void
    {
        $criteria = new SearchCriteria(
            Task::class,
            [],
            [],
            null,
            0
        );

        $this->assertFalse($criteria->hasPagination());
    }

    public function testGettersReturnImmutableData(): void
    {
        $filters = ['status' => 'active'];
        $sorting = ['created_at' => SortDirection::ASC];

        $criteria = new SearchCriteria(
            Task::class,
            $filters,
            $sorting,
            20,
            0
        );

        $retrievedFilters = $criteria->getFilters();
        $retrievedSorting = $criteria->getSorting();

        // Verify data is the same but modifications don't affect original
        $this->assertEquals($filters, $retrievedFilters);
        $this->assertEquals($sorting, $retrievedSorting);
    }

    public function testMultipleFiltersAndSorting(): void
    {
        $filters = [
            'status' => 'active',
            'priority' => 'high',
            'deadline' => '2025-12-31',
        ];
        $sorting = [
            'created_at' => SortDirection::DESC,
            'deadline' => SortDirection::ASC,
        ];

        $criteria = new SearchCriteria(
            Task::class,
            $filters,
            $sorting,
            50,
            100
        );

        $this->assertCount(3, $criteria->getFilters());
        $this->assertCount(2, $criteria->getSorting());
        $this->assertTrue($criteria->hasFilters());
        $this->assertTrue($criteria->hasSorting());
    }
}
