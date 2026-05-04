<?php

namespace App\Component\Centrifugal;

use App\Component\Centrifugal\Dto\ConnectionTokenDto;
use Symfony\Component\Security\Core\User\UserInterface;

interface CentrifugalInterface
{
    /**
     * @param string[] $channels
     */
    public function generateConnectionToken(UserInterface $user, array $channels = [], ?\DateInterval $ttl = null): ConnectionTokenDto;

    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $channel, array $data): void;
}
