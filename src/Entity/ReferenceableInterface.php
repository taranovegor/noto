<?php

namespace App\Entity;

use App\Enum\RefType;

interface ReferenceableInterface
{
    public static function getRefType(): RefType;

    public function getRef(): Ref;
}
