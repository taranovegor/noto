<?php

namespace App\Component\WebSocket\Recipient;

final readonly class WebSocketRecipient implements WebSocketRecipientInterface
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
