<?php

namespace App\Component\Broadcaster;

use App\Component\Broadcaster\Enum\BroadcastEvent;

interface BroadcasterInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function broadcast(string $namespace, string $channel, array $data, BroadcastEvent $event): void;
}
