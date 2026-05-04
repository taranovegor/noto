<?php

namespace App\Component\Broadcaster;

interface BroadcasterInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function broadcast(string $namespace, string $channel, array $data): void;
}
