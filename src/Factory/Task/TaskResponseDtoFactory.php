<?php

namespace App\Factory\Task;

use App\Dto\Task\TaskResponseDto;
use App\Entity\Attachment;
use App\Entity\Task;
use App\Enum\LinkKind;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Service\Link\LinkResolver;

readonly class TaskResponseDtoFactory
{
    public function __construct(
        private LinkResolver $linkResolver,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
    ) {
    }

    public function create(Task $task): TaskResponseDto
    {
        $resolved = $this->linkResolver->resolve($task, LinkKind::Ownership, Attachment::class);
        $attachments = array_map($this->attachmentResponseDtoFactory->create(...), $resolved) ?: null;

        return new TaskResponseDto(
            $task->id,
            $task->project?->id,
            $task->code,
            $task->name,
            $task->status,
            $task->priority,
            $task->deadline,
            $task->note,
            $task->createdAt,
            $task->updatedAt,
            $attachments,
        );
    }
}
