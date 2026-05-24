<?php

namespace App\Enum;

/**
 * Max length MUST be less than 50 charsets.
 */
enum RefType: string
{
    case Attachment = 'attachment';
    case Project = 'project';
    case Stash = 'stash';
    case Task = 'task';
    case Memo = 'memo';
    case Note = 'note';
    case Notebook = 'notebook';
    case User = 'user';
}
