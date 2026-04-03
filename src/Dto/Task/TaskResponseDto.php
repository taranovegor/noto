<?php

namespace App\Dto\Task;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Uid\Uuid;

readonly class TaskResponseDto
{
    public function __construct(
        public Uuid $id,
        public ?Uuid $projectId,
        public ?string $code,
        public string $name,
        public TaskStatus $status,
        public ?TaskPriority $priority,
        public ?\DateTimeInterface $deadline,
        public ?string $note,
        public \DateTimeInterface $createdAt,
        public \DateTimeInterface $updatedAt,
    ) {
    }
}
