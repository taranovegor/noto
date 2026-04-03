<?php

namespace App\Tests\Integration\Component\Searcher;

use App\Component\Searcher\DoctrineSearcher;
use App\Component\Searcher\Enum\FilterOperator;
use App\Component\Searcher\Enum\SortDirection;
use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;
use App\Dto\Task\SearchTaskDto;
use App\Entity\Task;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManager;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineSearcherTest extends KernelTestCase
{
    private DoctrineSearcher $searcher;
    private EntityManager $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->searcher = self::getContainer()->get(DoctrineSearcher::class);

        // Clean up existing data
        $this->em->createQuery('DELETE FROM App\Entity\Task')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Ref')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Project')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }

    public function testSearchWithoutFiltersOrSorting(): void
    {
        // Create test tasks
        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::Backlog;
        $task2 = new Task('Task 2');
        $task2->status = TaskStatus::InProgress;

        $this->em->persist($task1);
        $this->em->persist($task2);
        $this->em->flush();

        $dto = new SearchTaskDto([], [], new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(2, $result->getData());
    }

    public function testSearchWithEqualityFilter(): void
    {
        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::Backlog;
        $task2 = new Task('Task 2');
        $task2->status = TaskStatus::InProgress;
        $task3 = new Task('Task 3');
        $task3->status = TaskStatus::Backlog;

        $this->em->persist($task1);
        $this->em->persist($task2);
        $this->em->persist($task3);
        $this->em->flush();

        $filters = [
            new FilterCondition('status', FilterOperator::Eq, 'backlog'),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(2, $result->getData());
    }

    public function testSearchWithInFilter(): void
    {
        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::Backlog;
        $task2 = new Task('Task 2');
        $task2->status = TaskStatus::InProgress;
        $task3 = new Task('Task 3');
        $task3->status = TaskStatus::Done;

        $this->em->persist($task1);
        $this->em->persist($task2);
        $this->em->persist($task3);
        $this->em->flush();

        $filters = [
            new FilterCondition('status', FilterOperator::In, ['backlog', 'in_progress']),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(2, $result->getData());
    }

    public function testSearchWithSorting(): void
    {
        $task1 = new Task('Zebra');
        $task1->status = TaskStatus::Backlog;
        $task2 = new Task('Apple');
        $task2->status = TaskStatus::InProgress;
        $task3 = new Task('Banana');
        $task3->status = TaskStatus::Done;

        $this->em->persist($task1);
        $this->em->persist($task2);
        $this->em->persist($task3);
        $this->em->flush();

        $sorting = [
            new SortInstruction('created_at', SortDirection::ASC),
        ];
        $dto = new SearchTaskDto([], $sorting, new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(3, $result->getData());
        // Tasks should be ordered by creation time
        $tasks = $result->getData();
        $this->assertEquals('Zebra', $tasks[0]->name);
        $this->assertEquals('Apple', $tasks[1]->name);
        $this->assertEquals('Banana', $tasks[2]->name);
    }

    public function testSearchWithDescendingSorting(): void
    {
        $task1 = new Task('First');
        $this->em->persist($task1);
        $this->em->flush();

        $task2 = new Task('Second');
        $this->em->persist($task2);
        $this->em->flush();

        $task3 = new Task('Third');
        $this->em->persist($task3);
        $this->em->flush();

        $sorting = [
            new SortInstruction('id', SortDirection::DESC),
        ];
        $dto = new SearchTaskDto([], $sorting, new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $tasks = $result->getData();
        $this->assertEquals('Third', $tasks[0]->name);
        $this->assertEquals('Second', $tasks[1]->name);
        $this->assertEquals('First', $tasks[2]->name);
    }

    public function testSearchWithPagination(): void
    {
        // Create 30 tasks
        for ($i = 1; $i <= 30; ++$i) {
            $task = new Task("Task $i");
            $task->status = TaskStatus::Backlog;
            $this->em->persist($task);
        }
        $this->em->flush();

        $dto = new SearchTaskDto([], [], new PaginationDetails(10, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(10, $result->getData());
        $pagination = $result->getPagination();
        $this->assertNotNull($pagination);
        $this->assertEquals(30, $pagination->getTotal());
        $this->assertEquals(10, $pagination->getLimit());
        $this->assertEquals(0, $pagination->getOffset());
    }

    public function testSearchWithPaginationOffset(): void
    {
        // Create 30 tasks
        for ($i = 1; $i <= 30; ++$i) {
            $task = new Task("Task $i");
            $task->status = TaskStatus::Backlog;
            $this->em->persist($task);
        }
        $this->em->flush();

        $dto = new SearchTaskDto([], [], new PaginationDetails(10, 20));
        $result = $this->searcher->search($dto);

        $this->assertCount(10, $result->getData());
        $pagination = $result->getPagination();
        $this->assertEquals(30, $pagination->getTotal());
        $this->assertEquals(20, $pagination->getOffset());
    }

    public function testSearchWithoutPagination(): void
    {
        // Create 30 tasks
        for ($i = 1; $i <= 30; ++$i) {
            $task = new Task("Task $i");
            $task->status = TaskStatus::Backlog;
            $this->em->persist($task);
        }
        $this->em->flush();

        $dto = new SearchTaskDto([], [], new PaginationDetails(0, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(30, $result->getData());
        $this->assertNull($result->getPagination());
    }

    public function testSearchWithFilterAndSorting(): void
    {
        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::InProgress;
        $this->em->persist($task1);
        $this->em->flush();

        usleep(1000);

        $task2 = new Task('Task 2');
        $task2->status = TaskStatus::Backlog;
        $this->em->persist($task2);
        $this->em->flush();

        usleep(1000);

        $task3 = new Task('Task 3');
        $task3->status = TaskStatus::InProgress;
        $this->em->persist($task3);
        $this->em->flush();

        $filters = [
            new FilterCondition('status', FilterOperator::Eq, 'in_progress'),
        ];
        $sorting = [
            new SortInstruction('id', SortDirection::DESC),
        ];
        $dto = new SearchTaskDto($filters, $sorting, new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(2, $result->getData());
        $tasks = $result->getData();
        $this->assertEquals('Task 3', $tasks[0]->name);
        $this->assertEquals('Task 1', $tasks[1]->name);
    }

    public function testSearchEmptyResult(): void
    {
        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::Backlog;
        $this->em->persist($task1);
        $this->em->flush();

        $filters = [
            new FilterCondition('status', FilterOperator::Eq, 'done'),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(20, 0));
        $result = $this->searcher->search($dto);

        $this->assertCount(0, $result->getData());
        $pagination = $result->getPagination();
        $this->assertNotNull($pagination);
        $this->assertEquals(0, $pagination->getTotal());
    }

    public function testSearchWithInvalidDefinitionThrowsException(): void
    {
        $this->expectException(\TypeError::class);

        // Create a DTO without Searchable attribute (stdClass doesn't implement required interfaces)
        $dto = new \stdClass();
        $this->searcher->search($dto);
    }

    public function testSearchCountsCorrectlyWithFilters(): void
    {
        // Create mixed tasks
        $statusBacklog = TaskStatus::Backlog;
        $statusInProgress = TaskStatus::InProgress;

        for ($i = 1; $i <= 5; ++$i) {
            $task = new Task("Task $i");
            $task->status = 0 === $i % 2 ? $statusInProgress : $statusBacklog;
            $this->em->persist($task);
        }
        $this->em->flush();

        $filters = [
            new FilterCondition('status', FilterOperator::Eq, 'in_progress'),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(2, 0));
        $result = $this->searcher->search($dto);

        // Should have 2 in-progress tasks
        $pagination = $result->getPagination();
        $this->assertEquals(2, $pagination->getTotal());
        $this->assertCount(2, $result->getData());
    }
}
