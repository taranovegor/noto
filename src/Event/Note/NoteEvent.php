<?php

namespace App\Event\Note;

use App\Entity\Note;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class NoteEvent extends Event implements ReferenceableEventInterface
{
    public function __construct(
        public readonly Note $note,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Note;
    }
}
