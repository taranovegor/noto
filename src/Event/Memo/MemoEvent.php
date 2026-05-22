<?php

namespace App\Event\Memo;

use App\Entity\Memo;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class MemoEvent extends Event implements ReferenceableEventInterface
{
    public function __construct(
        public readonly Memo $memo,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Memo;
    }
}
