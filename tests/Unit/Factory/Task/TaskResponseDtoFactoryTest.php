<?php

namespace App\Tests\Unit\Factory\Task;

use App\Dto\Task\TaskResponseDto;
use App\Entity\Project;
use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Factory\Task\TaskResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class TaskResponseDtoFactoryTest extends TestCase
{
    private TaskResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TaskResponseDtoFactory();
    }

    public function testCreateConvertsTaskToDto(): void
    {
        $task = new Task('Test Task');
        $task->status = TaskStatus::InProgress;
        $task->priority = TaskPriority::High;
        $task->note = 'Test note';

        $dto = $this->factory->create($task);

        $this->assertInstanceOf(TaskResponseDto::class, $dto);
        $this->assertEquals($task->id, $dto->id);
        $this->assertEquals('Test Task', $dto->name);
        $this->assertEquals(TaskStatus::InProgress, $dto->status);
        $this->assertEquals(TaskPriority::High, $dto->priority);
        $this->assertEquals('Test note', $dto->note);
        $this->assertNull($dto->projectId);
        $this->assertNull($dto->code);
        $this->assertNull($dto->deadline);
    }

    public function testCreateWithProject(): void
    {
        $project = new Project('Test Project', 'TST');
        $task = new Task('Task with Project');
        $task->note = '';
        $task->setProject($project, 'TST-1');
        $task->status = TaskStatus::Done;

        $dto = $this->factory->create($task);

        $this->assertEquals($project->id, $dto->projectId);
        $this->assertEquals('TST-1', $dto->code);
    }

    public function testCreateWithDeadline(): void
    {
        $task = new Task('Task with Deadline');
        $deadline = new \DateTimeImmutable('2025-12-31');
        $task->deadline = $deadline;
        $task->note = '';
        $task->status = TaskStatus::Backlog;
        $task->priority = TaskPriority::Medium;

        $dto = $this->factory->create($task);

        $this->assertEquals($deadline, $dto->deadline);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $task = new Task('Task');
        $task->note = '';
        $task->status = TaskStatus::Backlog;

        $dto = $this->factory->create($task);

        $this->assertEquals($task->createdAt, $dto->createdAt);
        $this->assertEquals($task->updatedAt, $dto->updatedAt);
    }
}
