<?php

namespace App\Event\Project;

use App\Entity\Project;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ProjectEvent extends Event implements ReferenceableEventInterface
{
    public function __construct(
        public readonly Project $project,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Project;
    }
}
