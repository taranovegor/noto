<?php

namespace App\Service\Link;

use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;

interface LinkerInterface
{
    public function link(ReferenceableInterface $source, ReferenceableInterface $target, LinkKind $kind): void;

    public function unlink(ReferenceableInterface $source, ReferenceableInterface $target, LinkKind $kind): void;
}
