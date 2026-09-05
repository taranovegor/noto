<?php

namespace App\Service\User;

use App\Entity\User;
use App\Exception\EntityNotFoundException;
use App\Repository\UserRepository;
use App\Service\Flusher;

final class UserManager
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Flusher $flusher,
    ) {
    }

    public function create(string $email): User
    {
        $user = new User($email);
        $this->userRepository->add($user);
        $this->flusher->flush();

        return $user;
    }

    public function getByEmail(string $email): User
    {
        return $this->userRepository->findByEmail($email)
            ?? throw new EntityNotFoundException(User::class, $email);
    }
}
