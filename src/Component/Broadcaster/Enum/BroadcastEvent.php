<?php

namespace App\Component\Broadcaster\Enum;

enum BroadcastEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
