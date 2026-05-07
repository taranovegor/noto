<?php

namespace App\Event\Task;

use App\Entity\Task;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class TaskEvent extends Event implements ReferenceableEventInterface
{
    public function __construct(
        public readonly Task $task,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Task;
    }
}
