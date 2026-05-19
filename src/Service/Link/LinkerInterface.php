<?php

namespace App\Service\Link;

use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;

interface LinkerInterface
{
    public function link(Ref $source, Ref $target, LinkKind $kind): Link;

    public function unlink(Ref $source, Ref $target, LinkKind $kind): void;
}
