<?php

namespace App\Service\Task;

use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\UpdateTaskDto;
use App\Entity\Task;
use App\Exception\EntityNotFoundException;
use App\Repository\TaskRepository;
use App\Service\Flusher;
use App\Service\Project\ProjectManager;
use Symfony\Component\Uid\Uuid;

final readonly class TaskManager
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectManager $projectManager,
        private TaskCodeGenerator $codeGenerator,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateTaskDto $dto): Task
    {
        $task = new Task($dto->name);
        if ($dto->projectId) {
            $project = $this->projectManager->get($dto->projectId);
            $code = $this->codeGenerator->generate($project);
            $task->setProject($project, $code);
        }
        $task->status = $dto->status;
        $task->priority = $dto->priority;
        $task->deadline = $dto->deadline;
        $task->note = $dto->note ?? '';

        $this->taskRepository->add($task);

        $this->flusher->flush();

        return $task;
    }

    public function get(Uuid $id): Task
    {
        return $this->taskRepository->find($id) ?? throw new EntityNotFoundException(Task::class, $id);
    }

    public function update(Task $task, UpdateTaskDto $dto): void
    {
        if (null !== $dto->projectId && !$dto->projectId->equals($task->project?->id)) {
            $project = $this->projectManager->get($dto->projectId);
            $code = $this->codeGenerator->generate($project);
            $task->setProject($project, $code);
        }

        if (null !== $dto->name) {
            $task->name = $dto->name;
        }

        if (null !== $dto->status) {
            $task->status = $dto->status;
        }

        if (null !== $dto->priority) {
            $task->priority = $dto->priority;
        }

        if (null !== $dto->deadline) {
            $task->deadline = $dto->deadline;
        }

        if (null !== $dto->note) {
            $task->note = $dto->note;
        }

        $this->flusher->flush();
    }
}
