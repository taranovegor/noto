<?php

namespace App\Factory\Ref;

use App\Dto\Ref\RefResponseDto;
use App\Entity\Ref;

class RefResponseDtoFactory
{
    public function create(Ref $ref): RefResponseDto
    {
        return new RefResponseDto(
            $ref->id,
            $ref->type,
        );
    }
}
