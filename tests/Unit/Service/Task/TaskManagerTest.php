<?php

namespace App\Tests\Unit\Service\Task;

use App\Dto\Task\AttachTaskAttachmentsDto;
use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\UpdateTaskDto;
use App\Entity\Attachment;
use App\Entity\Project;
use App\Entity\Ref;
use App\Entity\Task;
use App\Enum\LinkKind;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Exception\EntityNotFoundException;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Project\ProjectManager;
use App\Service\Task\TaskCodeGenerator;
use App\Service\Task\TaskManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TaskManagerTest extends TestCase
{
    private function makeManager(
        ?TaskRepository $repo = null,
        ?ProjectManager $projectManager = null,
        ?TaskCodeGenerator $codeGenerator = null,
        ?LinkerInterface $linker = null,
        ?Flusher $flusher = null,
    ): TaskManager {
        $projectRepository = $this->createStub(ProjectRepository::class);
        $projectFlusher = $this->createStub(Flusher::class);

        return new TaskManager(
            $repo ?? $this->createStub(TaskRepository::class),
            $projectManager ?? new ProjectManager($projectRepository, $projectFlusher),
            $codeGenerator ?? $this->createStub(TaskCodeGenerator::class),
            $linker ?? $this->createStub(LinkerInterface::class),
            $flusher ?? $this->createStub(Flusher::class),
        );
    }

    public function testCreateTaskWithoutProject(): void
    {
        $repo = $this->createMock(TaskRepository::class);
        $codeGenerator = $this->createMock(TaskCodeGenerator::class);
        $flusher = $this->createMock(Flusher::class);

        $dto = new CreateTaskDto(
            projectId: null,
            name: 'Test Task',
            status: TaskStatus::Backlog,
            priority: TaskPriority::High,
            deadline: null,
            note: 'Test note',
        );

        $repo->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Task $task) {
                return 'Test Task' === $task->name
                    && TaskStatus::Backlog === $task->status
                    && TaskPriority::High === $task->priority
                    && 'Test note' === $task->note
                    && null === $task->project
                    && null === $task->code;
            }));
        $codeGenerator->expects($this->never())->method('generate');
        $flusher->expects($this->once())->method('flush');

        $task = $this->makeManager(repo: $repo, codeGenerator: $codeGenerator, flusher: $flusher)
            ->create($dto);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->name);
        $this->assertEquals(TaskStatus::Backlog, $task->status);
        $this->assertEquals(TaskPriority::High, $task->priority);
        $this->assertEquals('Test note', $task->note);
        $this->assertNull($task->project);
        $this->assertNull($task->code);
    }

    public function testCreateTaskWithProject(): void
    {
        $project = new Project('Project', 'PRJ');
        $projectId = $project->id;

        $repo = $this->createMock(TaskRepository::class);
        $codeGenerator = $this->createMock(TaskCodeGenerator::class);
        $flusher = $this->createMock(Flusher::class);

        $projectRepo = $this->createMock(ProjectRepository::class);
        $projectRepo->expects($this->once())
            ->method('find')
            ->with($projectId)
            ->willReturn($project);

        $projectManager = new ProjectManager($projectRepo, $this->createStub(Flusher::class));

        $dto = new CreateTaskDto(
            projectId: $projectId,
            name: 'Test Task',
            status: TaskStatus::InProgress,
            priority: null,
            deadline: null,
            note: '',
        );

        $codeGenerator->expects($this->once())
            ->method('generate')
            ->with($project)
            ->willReturn('PRJ-1');

        $repo->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Task $task) use ($project) {
                return 'Test Task' === $task->name
                    && TaskStatus::InProgress === $task->status
                    && null === $task->priority
                    && '' === $task->note
                    && $task->project === $project
                    && 'PRJ-1' === $task->code;
            }));
        $flusher->expects($this->once())->method('flush');

        $task = $this->makeManager(
            repo: $repo,
            projectManager: $projectManager,
            codeGenerator: $codeGenerator,
            flusher: $flusher,
        )->create($dto);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->name);
        $this->assertEquals($project, $task->project);
        $this->assertEquals('PRJ-1', $task->code);
    }

    public function testGetTaskReturnsTask(): void
    {
        $id = Uuid::v7();
        $task = new Task('Test Task');

        $repo = $this->createMock(TaskRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($task);

        $result = $this->makeManager(repo: $repo)->get($id);

        $this->assertEquals($task, $result);
    }

    public function testGetTaskThrowsEntityNotFoundExceptionWhenNotFound(): void
    {
        $id = Uuid::v7();

        $repo = $this->createMock(TaskRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);

        $this->makeManager(repo: $repo)->get($id);
    }

    public function testUpdateTaskPartially(): void
    {
        $task = new Task('Old Name');
        $task->status = TaskStatus::Backlog;
        $task->priority = TaskPriority::Low;

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $dto = new UpdateTaskDto(
            projectId: null,
            name: 'New Name',
            priority: TaskPriority::High,
            status: null,
            deadline: null,
            note: null,
        );

        $this->makeManager(flusher: $flusher)->update($task, $dto);

        $this->assertEquals('New Name', $task->name);
        $this->assertEquals(TaskPriority::High, $task->priority);
        $this->assertEquals(TaskStatus::Backlog, $task->status);
    }

    public function testUpdateTaskChangesAllFields(): void
    {
        $task = new Task('Old Name');
        $deadline = new \DateTimeImmutable('2025-12-31');

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $dto = new UpdateTaskDto(
            projectId: null,
            name: 'New Name',
            priority: TaskPriority::Medium,
            status: TaskStatus::Done,
            deadline: $deadline,
            note: 'New note',
        );

        $this->makeManager(flusher: $flusher)->update($task, $dto);

        $this->assertEquals('New Name', $task->name);
        $this->assertEquals(TaskPriority::Medium, $task->priority);
        $this->assertEquals(TaskStatus::Done, $task->status);
        $this->assertEquals($deadline, $task->deadline);
        $this->assertEquals('New note', $task->note);
    }

    public function testCreateTaskWithAttachmentsLinksOwnership(): void
    {
        $a1 = new Attachment();
        $a2 = new Attachment();

        $repo = $this->createMock(TaskRepository::class);
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $dto = new CreateTaskDto(
            projectId: null,
            name: 'Task with files',
            status: TaskStatus::Backlog,
            attachments: [$a1, $a2],
        );

        $repo->expects($this->once())->method('add');
        $linker->expects($this->exactly(2))->method('link')
            ->with($this->isInstanceOf(Ref::class), $this->isInstanceOf(Ref::class), LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $task = $this->makeManager(repo: $repo, linker: $linker, flusher: $flusher)
            ->create($dto);
        $this->assertInstanceOf(Task::class, $task);
    }

    public function testAttachLinksEachAttachment(): void
    {
        $task = new Task('Task');
        $a1 = new Attachment();
        $a2 = new Attachment();

        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->exactly(2))->method('link')
            ->with($this->equalTo($task->getRef()), $this->isInstanceOf(Ref::class), LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager(linker: $linker, flusher: $flusher)
            ->attach($task, new AttachTaskAttachmentsDto(attachments: [$a1, $a2]));
    }

    public function testDetachCallsUnlink(): void
    {
        $task = new Task('Task');
        $attachment = new Attachment();

        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->once())->method('unlink')
            ->with($this->equalTo($task->getRef()), $this->equalTo($attachment->getRef()), LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager(linker: $linker, flusher: $flusher)
            ->detach($task, $attachment);
    }
}
