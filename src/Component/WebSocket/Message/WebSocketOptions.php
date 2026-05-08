<?php

namespace App\Component\WebSocket\Message;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

class WebSocketOptions implements MessageOptionsInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $channel,
        private array $data = [],
        private ?string $event = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function getRecipientId(): string
    {
        return $this->getChannel();
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
