<?php

namespace App\Dto\Task;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class TaskResponseDto
{
    public function __construct(
        #[Groups(['task:read', 'task:list'])]
        public Uuid $id,
        #[Groups(['task:read', 'task:list'])]
        public ?Uuid $projectId,
        #[Groups(['task:read', 'task:list'])]
        public ?string $code,
        #[Groups(['task:read', 'task:list'])]
        public string $name,
        #[Groups(['task:read', 'task:list'])]
        public TaskStatus $status,
        #[Groups(['task:read', 'task:list'])]
        public ?TaskPriority $priority,
        #[Groups(['task:read', 'task:list'])]
        public ?\DateTimeInterface $deadline,
        #[Groups(['task:read'])]
        public ?string $note,
        #[Groups(['task:read', 'task:list'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['task:read', 'task:list'])]
        public \DateTimeInterface $updatedAt,
    ) {
    }
}
