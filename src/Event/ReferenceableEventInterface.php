<?php

namespace App\Event;

use App\Enum\RefType;

interface ReferenceableEventInterface
{
    public static function getRefType(): RefType;
}
