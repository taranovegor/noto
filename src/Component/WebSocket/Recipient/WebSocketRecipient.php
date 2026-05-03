<?php

namespace App\Component\WebSocket\Recipient;

use Symfony\Component\Notifier\Recipient\RecipientInterface;

final readonly class WebSocketRecipient implements RecipientInterface
{
    public function __construct(
        private string $channel,
    ) {
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
