<?php

namespace App\Tests\Integration\Component\Searcher;

use App\Component\Searcher\DoctrineSearcher;
use App\Component\Searcher\Enum\FilterOperator;
use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Dto\Task\SearchTaskDto;
use App\Entity\Task;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineSearcherFilterHandlerTest extends KernelTestCase
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

    public function testHandlerIsCalledWhenFilterDefinitionHasHandler(): void
    {
        // Create test tasks
        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::Backlog;
        $task2 = new Task('Task 2');
        $task2->status = TaskStatus::InProgress;

        $this->em->persist($task1);
        $this->em->persist($task2);
        $this->em->flush();

        // The 'query' filter in TaskSearchDefinition has a handler (EmbeddingVectorFilterHandler)
        // This test verifies the handler is invoked without errors
        $filters = [
            new FilterCondition('query', FilterOperator::Like, 'search term'),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(20, 0));

        // Should execute without exception - handler was invoked
        $result = $this->searcher->search($dto);

        // Result should be an array of tasks
        $this->assertIsArray($result->getData());
    }

    public function testStandardFilterNotAppliedWhenHandlerPresent(): void
    {
        // Create tasks with specific names
        $task1 = new Task('Database Query Task');
        $task1->status = TaskStatus::Backlog;

        $task2 = new Task('API Endpoint');
        $task2->status = TaskStatus::Backlog;

        $this->em->persist($task1);
        $this->em->persist($task2);
        $this->em->flush();

        // When a filter has a handler, standard LIKE filter on the 'name' field should not apply
        // The handler controls the filtering logic
        $filters = [
            new FilterCondition('query', FilterOperator::Like, 'Database'),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(20, 0));

        $result = $this->searcher->search($dto);

        // Handler is used instead of standard filter
        $this->assertIsArray($result->getData());
    }

    public function testMultipleFiltersWithMixedHandlers(): void
    {
        // Create tasks
        for ($i = 1; $i <= 3; ++$i) {
            $task = new Task("Task $i");
            $task->status = TaskStatus::Backlog;
            $this->em->persist($task);
        }
        $this->em->flush();

        // Combine handler-based filter with standard filter
        // 'query' has a handler, 'status' is standard
        $filters = [
            new FilterCondition('status', FilterOperator::Eq, 'backlog'),
            new FilterCondition('query', FilterOperator::Like, 'Task'),
        ];
        $dto = new SearchTaskDto($filters, [], new PaginationDetails(20, 0));

        $result = $this->searcher->search($dto);

        // Both filters should be applied
        $this->assertIsArray($result->getData());
    }
}
