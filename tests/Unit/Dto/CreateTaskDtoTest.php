<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Task\CreateTaskDto;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CreateTaskDtoTest extends TestCase
{
    public function testCreateTaskDtoWithAllFields(): void
    {
        $projectId = Uuid::v7();
        $deadline = new \DateTimeImmutable('2025-12-31');

        $dto = new CreateTaskDto(
            projectId: $projectId,
            name: 'Test Task',
            status: TaskStatus::InProgress,
            priority: TaskPriority::High,
            deadline: $deadline,
            note: 'Test note',
        );

        $this->assertEquals($projectId, $dto->projectId);
        $this->assertEquals('Test Task', $dto->name);
        $this->assertEquals(TaskStatus::InProgress, $dto->status);
        $this->assertEquals(TaskPriority::High, $dto->priority);
        $this->assertEquals($deadline, $dto->deadline);
        $this->assertEquals('Test note', $dto->note);
    }

    public function testCreateTaskDtoWithoutOptionalFields(): void
    {
        $dto = new CreateTaskDto(
            projectId: null,
            name: 'Simple Task',
            status: TaskStatus::Backlog,
        );

        $this->assertNull($dto->projectId);
        $this->assertEquals('Simple Task', $dto->name);
        $this->assertEquals(TaskStatus::Backlog, $dto->status);
        $this->assertNull($dto->priority);
        $this->assertNull($dto->deadline);
        $this->assertEquals('', $dto->note);
    }

    public function testCreateTaskDtoIsReadonly(): void
    {
        $dto = new CreateTaskDto(
            projectId: null,
            name: 'Task',
            status: TaskStatus::Backlog,
        );

        $this->expectException(\Error::class);
        $dto->name = 'Modified';
    }
}
