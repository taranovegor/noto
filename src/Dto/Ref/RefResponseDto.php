<?php

namespace App\Dto\Ref;

use App\Enum\RefType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class RefResponseDto
{
    public function __construct(
        #[Groups(['ref:read'])]
        public Uuid $id,
        #[Groups(['ref:read'])]
        public RefType $type,
    ) {
    }
}
