<?php

namespace App\Tests\Unit\Service\Task;

use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\UpdateTaskDto;
use App\Entity\Project;
use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Exception\EntityNotFoundException;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\Flusher;
use App\Service\Project\ProjectManager;
use App\Service\Task\TaskCodeGenerator;
use App\Service\Task\TaskManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TaskManagerTest extends TestCase
{
    private TaskRepository&MockObject $taskRepository;
    private ProjectManager $projectManager;
    private TaskCodeGenerator&MockObject $codeGenerator;
    private Flusher&MockObject $flusher;
    private TaskManager $taskManager;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $projectRepository = $this->createStub(ProjectRepository::class);
        $this->projectManager = new ProjectManager($projectRepository);
        $this->codeGenerator = $this->createMock(TaskCodeGenerator::class);
        $this->flusher = $this->createMock(Flusher::class);

        $this->taskManager = new TaskManager(
            $this->taskRepository,
            $this->projectManager,
            $this->codeGenerator,
            $this->flusher,
        );
    }

    public function testCreateTaskWithoutProject(): void
    {
        $dto = new CreateTaskDto(
            projectId: null,
            name: 'Test Task',
            status: TaskStatus::Backlog,
            priority: TaskPriority::High,
            deadline: null,
            note: 'Test note',
        );

        $this->taskRepository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Task $task) {
                return 'Test Task' === $task->name
                    && TaskStatus::Backlog === $task->status
                    && TaskPriority::High === $task->priority
                    && 'Test note' === $task->note
                    && null === $task->project
                    && null === $task->code;
            }));
        $this->codeGenerator->expects($this->never())->method('generate');
        $this->flusher->expects($this->once())->method('flush');

        $task = $this->taskManager->create($dto);

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

        $projectRepositoryMock = $this->createMock(ProjectRepository::class);
        $projectRepositoryMock->expects($this->once())
            ->method('find')
            ->with($projectId)
            ->willReturn($project);

        $projectManager = new ProjectManager($projectRepositoryMock);

        $taskManager = new TaskManager(
            $this->taskRepository,
            $projectManager,
            $this->codeGenerator,
            $this->flusher,
        );

        $dto = new CreateTaskDto(
            projectId: $projectId,
            name: 'Test Task',
            status: TaskStatus::InProgress,
            priority: null,
            deadline: null,
            note: '',
        );

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->with($project)
            ->willReturn('PRJ-1');

        $this->taskRepository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Task $task) use ($project) {
                return 'Test Task' === $task->name
                    && TaskStatus::InProgress === $task->status
                    && null === $task->priority
                    && '' === $task->note
                    && $task->project === $project
                    && 'PRJ-1' === $task->code;
            }));
        $this->flusher->expects($this->once())->method('flush');

        $task = $taskManager->create($dto);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->name);
        $this->assertEquals($project, $task->project);
        $this->assertEquals('PRJ-1', $task->code);
    }

    public function testGetTaskReturnsTask(): void
    {
        $id = Uuid::v7();
        $task = new Task('Test Task');

        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($task);

        $this->codeGenerator->expects($this->never())->method('generate');
        $this->flusher->expects($this->never())->method('flush');

        $result = $this->taskManager->get($id);

        $this->assertEquals($task, $result);
    }

    public function testGetTaskThrowsEntityNotFoundExceptionWhenNotFound(): void
    {
        $id = Uuid::v7();

        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->codeGenerator->expects($this->never())->method('generate');
        $this->flusher->expects($this->never())->method('flush');

        $this->expectException(EntityNotFoundException::class);

        $this->taskManager->get($id);
    }

    public function testUpdateTaskPartially(): void
    {
        $task = new Task('Old Name');
        $task->status = TaskStatus::Backlog;
        $task->priority = TaskPriority::Low;

        $dto = new UpdateTaskDto(
            projectId: null,
            name: 'New Name',
            priority: TaskPriority::High,
            status: null,
            deadline: null,
            note: null,
        );

        $this->taskRepository->expects($this->never())->method('add');
        $this->codeGenerator->expects($this->never())->method('generate');
        $this->flusher->expects($this->once())->method('flush');

        $this->taskManager->update($task, $dto);

        $this->assertEquals('New Name', $task->name);
        $this->assertEquals(TaskPriority::High, $task->priority);
        $this->assertEquals(TaskStatus::Backlog, $task->status);
    }

    public function testUpdateTaskChangesAllFields(): void
    {
        $task = new Task('Old Name');
        $deadline = new \DateTimeImmutable('2025-12-31');

        $dto = new UpdateTaskDto(
            projectId: null,
            name: 'New Name',
            priority: TaskPriority::Medium,
            status: TaskStatus::Done,
            deadline: $deadline,
            note: 'New note',
        );

        $this->taskRepository->expects($this->never())->method('add');
        $this->codeGenerator->expects($this->never())->method('generate');
        $this->flusher->expects($this->once())->method('flush');

        $this->taskManager->update($task, $dto);

        $this->assertEquals('New Name', $task->name);
        $this->assertEquals(TaskPriority::Medium, $task->priority);
        $this->assertEquals(TaskStatus::Done, $task->status);
        $this->assertEquals($deadline, $task->deadline);
        $this->assertEquals('New note', $task->note);
    }
}
