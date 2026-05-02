<?php

namespace App\Service\User;

use App\Entity\User;
use App\Exception\EntityNotFoundException;
use App\Repository\UserRepository;
use App\Service\Flusher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManager
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Flusher $flusher,
    ) {
    }

    public function create(string $email, string $password): User
    {
        $user = new User($email, $password, $this->passwordHasher);
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
