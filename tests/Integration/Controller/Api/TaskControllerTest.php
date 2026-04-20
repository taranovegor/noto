<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\Project;
use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class TaskControllerTest extends WebTestCase
{
    private function cleanupTasks(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\Task')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();
        $em->createQuery('DELETE FROM App\Entity\Project')->execute();
    }

    // --- Create Tests ---

    public function testCreateTaskWithoutProject(): void
    {
        $client = self::createClient();

        $data = [
            'projectId' => null,
            'name' => 'New Task',
            'status' => 'backlog',
            'priority' => 'high',
            'deadline' => null,
            'note' => 'Test note',
        ];

        $client->request('POST', '/api/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('New Task', $response['name']);
        $this->assertEquals('backlog', $response['status']);
        $this->assertEquals('high', $response['priority']);
        $this->assertEquals('Test note', $response['note']);
        $this->assertNull($response['projectId']);
        $this->assertNull($response['code']);
    }

    public function testCreateTaskValidationError(): void
    {
        $client = self::createClient();

        $data = [
            'projectId' => null,
            'name' => '',  // Invalid: empty name
            'status' => 'backlog',
            'priority' => null,
            'deadline' => null,
            'note' => '',
        ];

        $client->request('POST', '/api/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Validation Failed', $response['title']);
        $this->assertEquals(422, $response['status']);
        $this->assertIsArray($response['violations']);
    }

    public function testCreateTaskWithNonExistentProject(): void
    {
        $client = self::createClient();

        $data = [
            'projectId' => Uuid::v7()->toRfc4122(),  // Non-existent project
            'name' => 'Task with invalid project',
            'status' => 'backlog',
            'priority' => 'high',
            'deadline' => null,
            'note' => 'Test',
        ];

        $client->request('POST', '/api/tasks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Validation Failed', $response['title']);
        $this->assertIsArray($response['violations']);
        $violations = array_filter($response['violations'], fn ($v) => false !== strpos($v['propertyPath'], 'projectId'));
        $this->assertNotEmpty($violations, 'Expected EntityExists violation for projectId field');
    }

    // --- Get Tests ---

    public function testGetTaskReturnsTaskData(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        // Create a test task
        $task = new Task('Get Test Task');
        $task->status = TaskStatus::InProgress;
        $task->priority = TaskPriority::Medium;
        $em->persist($task);
        $em->flush();

        $client->request('GET', '/api/tasks/'.$task->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($task->id->toRfc4122(), $response['id']);
        $this->assertEquals('Get Test Task', $response['name']);
        $this->assertEquals('in_progress', $response['status']);
        $this->assertEquals('medium', $response['priority']);
    }

    public function testGetTaskNotFound(): void
    {
        $client = self::createClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $client->request('GET', '/api/tasks/'.$nonExistentId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetTaskInvalidUuid(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/tasks/invalid-uuid');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- Update Tests ---

    public function testUpdateTaskPartially(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task = new Task('Update Test Task');
        $task->status = TaskStatus::Backlog;
        $task->priority = TaskPriority::Low;
        $em->persist($task);
        $em->flush();

        $project = new Project('Test Project', 'TST');
        $em->persist($project);
        $em->flush();

        $data = [
            'projectId' => $project->id->toRfc4122(),
            'name' => 'Updated Name',
            'status' => null,
            'priority' => 'high',
            'deadline' => null,
            'note' => null,
        ];

        $client->request('PATCH', '/api/tasks/'.$task->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Name', $response['name']);
        $this->assertEquals('high', $response['priority']);
        $this->assertEquals('backlog', $response['status']);  // Should remain unchanged
    }

    public function testUpdateNonExistentTask(): void
    {
        $client = self::createClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $project = new Project('Default Project', 'DEF');
        $em->persist($project);
        $em->flush();

        $data = [
            'projectId' => $project->id->toRfc4122(),
            'name' => 'Should not create',
            'status' => null,
            'priority' => null,
            'deadline' => null,
            'note' => null,
        ];

        $client->request('PATCH', '/api/tasks/'.$nonExistentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTaskWithNonExistentProject(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task = new Task('Update Test Task');
        $em->persist($task);
        $em->flush();

        $data = [
            'projectId' => Uuid::v7()->toRfc4122(),  // Non-existent project
            'name' => null,
            'status' => null,
            'priority' => null,
            'deadline' => null,
            'note' => null,
        ];

        $client->request('PATCH', '/api/tasks/'.$task->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Validation Failed', $response['title']);
        $this->assertIsArray($response['violations']);
        $violations = array_filter($response['violations'], fn ($v) => false !== strpos($v['propertyPath'], 'projectId'));
        $this->assertNotEmpty($violations, 'Expected EntityExists violation for projectId field');
    }

    // --- List Tests ---

    public function testListTasksWithoutFilters(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task1 = new Task('Task 1');
        $task2 = new Task('Task 2');
        $em->persist($task1);
        $em->persist($task2);
        $em->flush();

        $client->request('GET', '/api/tasks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertCount(2, $response['data']);
    }

    public function testListTasksWithStatusFilter(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task1 = new Task('Active Task');
        $task1->status = TaskStatus::InProgress;
        $task2 = new Task('Done Task');
        $task2->status = TaskStatus::Done;
        $task3 = new Task('Active Task 2');
        $task3->status = TaskStatus::InProgress;

        $em->persist($task1);
        $em->persist($task2);
        $em->persist($task3);
        $em->flush();

        $client->request('GET', '/api/tasks?filter[status]=in_progress');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals('Active Task', $response['data'][0]['name']);
    }

    public function testListTasksWithMultipleStatusFilters(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task1 = new Task('Task 1');
        $task1->status = TaskStatus::Backlog;
        $task2 = new Task('Task 2');
        $task2->status = TaskStatus::InProgress;
        $task3 = new Task('Task 3');
        $task3->status = TaskStatus::Done;

        $em->persist($task1);
        $em->persist($task2);
        $em->persist($task3);
        $em->flush();

        $client->request('GET', '/api/tasks?filter[status]=in:backlog,in_progress');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testListTasksWithSorting(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task1 = new Task('First');
        $task2 = new Task('Second');
        $task3 = new Task('Third');

        $em->persist($task1);
        $em->persist($task2);
        $em->persist($task3);
        $em->flush();

        // Get in reverse creation order
        $client->request('GET', '/api/tasks?sort=-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Third', $response['data'][0]['name']);
        $this->assertEquals('Second', $response['data'][1]['name']);
        $this->assertEquals('First', $response['data'][2]['name']);
    }

    public function testListTasksWithPagination(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        // Create 30 tasks
        for ($i = 1; $i <= 30; ++$i) {
            $task = new Task("Task $i");
            $em->persist($task);
        }
        $em->flush();

        $client->request('GET', '/api/tasks?limit=10&offset=0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(10, $response['data']);
        $this->assertEquals(30, $response['pagination']['total']);
        $this->assertEquals(10, $response['pagination']['limit']);
        $this->assertEquals(0, $response['pagination']['offset']);
    }

    public function testListTasksWithPaginationOffset(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        for ($i = 1; $i <= 30; ++$i) {
            $task = new Task("Task $i");
            $em->persist($task);
        }
        $em->flush();

        $client->request('GET', '/api/tasks?limit=10&offset=20');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(10, $response['data']);
        $this->assertEquals(20, $response['pagination']['offset']);
    }

    public function testListTasksWithoutPagination(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        for ($i = 1; $i <= 30; ++$i) {
            $task = new Task("Task $i");
            $em->persist($task);
        }
        $em->flush();

        $client->request('GET', '/api/tasks?limit=0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(30, $response['data']);
        $this->assertNull($response['pagination']);
    }

    public function testListTasksWithFilterAndSorting(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task1 = new Task('Task A');
        $task1->status = TaskStatus::InProgress;
        $task2 = new Task('Task B');
        $task2->status = TaskStatus::Backlog;
        $task3 = new Task('Task C');
        $task3->status = TaskStatus::InProgress;

        $em->persist($task1);
        $em->persist($task2);
        $em->persist($task3);
        $em->flush();

        $client->request('GET', '/api/tasks?filter[status]=in_progress&sort=-id');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals('Task C', $response['data'][0]['name']);
        $this->assertEquals('Task A', $response['data'][1]['name']);
    }

    public function testListTasksResponseStructure(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task = new Task('Test Task');
        $task->status = TaskStatus::InProgress;
        $task->priority = TaskPriority::High;
        $em->persist($task);
        $em->flush();

        $client->request('GET', '/api/tasks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertIsArray($response['pagination']);
        $this->assertArrayHasKey('id', $response['data'][0]);
        $this->assertArrayHasKey('name', $response['data'][0]);
        $this->assertArrayHasKey('status', $response['data'][0]);
        $this->assertArrayHasKey('total', $response['pagination']);
        $this->assertArrayHasKey('limit', $response['pagination']);
        $this->assertArrayHasKey('offset', $response['pagination']);
    }

    public function testListTasksIgnoresUnknownFilters(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task1 = new Task('Task 1');
        $task2 = new Task('Task 2');
        $em->persist($task1);
        $em->persist($task2);
        $em->flush();

        // Try to filter by unknown field (should be ignored) with valid status
        $client->request('GET', '/api/tasks?filter[unknown_field]=value&filter[status]=backlog');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Should return all tasks because unknown filter is ignored
        // and status filter is processed
        $this->assertIsArray($response['data']);
    }

    public function testListTasksWithEmptyFilterValue(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanupTasks();

        $task = new Task('Test');
        $em->persist($task);
        $em->flush();

        $client->request('GET', '/api/tasks?filter[status]=');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Empty filter should be ignored
        $this->assertCount(1, $response['data']);
    }
}
