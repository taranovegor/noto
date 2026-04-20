<?php

namespace App\Dto\Task;

use App\Component\Validator\Constraint\EntityExists;
use App\Entity\Project;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Uid\Uuid;

final readonly class UpdateTaskDto
{
    public function __construct(
        #[EntityExists(entityClass: Project::class, field: 'id')]
        public ?Uuid $projectId = null,
        public ?string $name = null,
        public ?TaskPriority $priority = null,
        public ?TaskStatus $status = null,
        public ?\DateTimeImmutable $deadline = null,
        public ?string $note = null,
    ) {
    }
}
