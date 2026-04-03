<?php

namespace App\Enum;

/**
 * Max length MUST be less than 20 charsets.
 */
enum TaskStatus: string
{
    case Backlog = 'backlog';
    case InProgress = 'in_progress';
    case Done = 'done';
}
