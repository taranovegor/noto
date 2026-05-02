<?php

namespace App\Dto\User;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class UserResponseDto
{
    public function __construct(
        #[Groups(['user:read'])]
        public Uuid $id,
        #[Groups(['user:read'])]
        public string $email,
    ) {
    }
}
