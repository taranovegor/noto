<?php

namespace App\Tests\Unit\Factory\Task;

use App\Dto\Task\TaskResponseDto;
use App\Entity\Attachment;
use App\Entity\Project;
use App\Entity\Task;
use App\Enum\LinkKind;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Service\Link\LinkResolver;
use PHPUnit\Framework\TestCase;

class TaskResponseDtoFactoryTest extends TestCase
{
    private TaskResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $linkResolver = $this->createStub(LinkResolver::class);
        $attachmentResponseDtoFactory = $this->createStub(AttachmentResponseDtoFactory::class);
        $this->factory = new TaskResponseDtoFactory($linkResolver, $attachmentResponseDtoFactory);
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
        $this->assertNull($dto->attachments);
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

    public function testCreateWithAttachmentsResolvesViaLinkResolver(): void
    {
        $attachment = new Attachment();
        $task = new Task('Task with attachment');
        $task->note = '';
        $task->status = TaskStatus::Backlog;

        $linkResolver = $this->createMock(LinkResolver::class);
        $linkResolver->expects($this->once())
            ->method('resolve')
            ->with($task->getRef(), LinkKind::Ownership, Attachment::class)
            ->willReturn([$attachment]);

        $attachmentResponseDtoFactory = $this->createMock(AttachmentResponseDtoFactory::class);
        $attachmentResponseDtoFactory->expects($this->once())
            ->method('create')
            ->with($attachment);

        $factory = new TaskResponseDtoFactory($linkResolver, $attachmentResponseDtoFactory);
        $dto = $factory->create($task);

        $this->assertNotNull($dto->attachments);
        $this->assertCount(1, $dto->attachments);
    }
}
