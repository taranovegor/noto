<?php

namespace App\Component\Broadcaster\Notification;

use App\Component\WebSocket\Message\WebSocketOptions;
use App\Component\WebSocket\Recipient\WebSocketRecipientInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class BroadcastNotification extends Notification implements ChatNotificationInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
    ) {
        parent::__construct(channels: ['chat']);
    }

    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ?ChatMessage
    {
        if (!$recipient instanceof WebSocketRecipientInterface) {
            return null;
        }

        return new ChatMessage($this->getSubject(), new WebSocketOptions($recipient->getChannel(), $this->data));
    }
}
