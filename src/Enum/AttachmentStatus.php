<?php

namespace App\Enum;

enum AttachmentStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
}
