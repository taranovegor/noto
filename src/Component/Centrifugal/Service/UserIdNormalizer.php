<?php

namespace App\Component\Centrifugal\Service;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class UserIdNormalizer
{
    public function normalize(UserInterface $user): string
    {
        return md5($user->getUserIdentifier());
    }
}
