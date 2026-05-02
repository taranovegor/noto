<?php

namespace App\Factory\User;

use App\Dto\User\UserResponseDto;
use App\Entity\User;

final class UserResponseDtoFactory
{
    public function create(User $user): UserResponseDto
    {
        return new UserResponseDto(
            $user->id,
            $user->getUserIdentifier(),
        );
    }
}
