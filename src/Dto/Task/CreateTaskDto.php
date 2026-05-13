<?php

namespace App\Dto\Task;

use App\Component\Validator\Constraint\EntityExists;
use App\Entity\Attachment;
use App\Entity\Project;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Validator\RefNotOwned;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTaskDto
{
    /**
     * @param Attachment[]|null $attachments
     */
    public function __construct(
        #[EntityExists(entityClass: Project::class, field: 'id')]
        public ?Uuid $projectId,
        #[Assert\NotBlank]
        public string $name,
        public TaskStatus $status,
        public ?TaskPriority $priority = null,
        public ?\DateTimeImmutable $deadline = null,
        public string $note = '',
        #[Assert\All([new RefNotOwned()])]
        public ?array $attachments = null,
    ) {
    }
}
