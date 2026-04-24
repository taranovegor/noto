<?php

namespace App\Entity;

interface ReferenceableInterface
{
    public function getRef(): Ref;
}
