<?php

namespace App\Enum;

/**
 * Max length MUST be less than 20 charsets.
 */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
