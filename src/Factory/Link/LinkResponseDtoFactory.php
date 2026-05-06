<?php

namespace App\Factory\Link;

use App\Dto\Link\LinkResponseDto;
use App\Entity\Link;

class LinkResponseDtoFactory
{
    public function create(Link $link): LinkResponseDto
    {
        return new LinkResponseDto(
            $link->id,
            $link->source->id,
            $link->sourceType,
            $link->target->id,
            $link->targetType,
            $link->kind,
            $link->createdAt,
        );
    }
}
