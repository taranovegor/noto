<?php

namespace App\Event\User;

use App\Entity\User;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class UserEvent extends Event implements ReferenceableEventInterface
{
    public function __construct(
        public readonly User $user,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::User;
    }
}
