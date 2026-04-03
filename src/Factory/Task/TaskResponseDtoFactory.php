<?php

namespace App\Factory\Task;

use App\Dto\Task\TaskResponseDto;
use App\Entity\Task;

class TaskResponseDtoFactory
{
    public function create(Task $task): TaskResponseDto
    {
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
        );
    }
}
