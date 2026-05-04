<?php

namespace App\Component\Broadcaster\Enum;

enum BroadcastEvent
{
    case Created;
    case Updated;
    case Deleted;
}
