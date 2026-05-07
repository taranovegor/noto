<?php

namespace App\Event\Stash;

use App\Entity\Stash;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class StashEvent extends Event implements ReferenceableEventInterface
{
    public function __construct(
        public readonly Stash $stash,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Stash;
    }
}
