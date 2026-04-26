<?php

namespace App\Enum;

/**
 * Max length MUST be less than 50 charsets.
 */
enum RefType: string
{
    case Project = 'project';
    case Task = 'task';
    case Note = 'note';
}
