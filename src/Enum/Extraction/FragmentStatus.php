<?php

namespace App\Enum\Extraction;

enum FragmentStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Failed = 'failed';
}
