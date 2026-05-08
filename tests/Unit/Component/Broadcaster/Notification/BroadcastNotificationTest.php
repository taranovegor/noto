<?php

namespace App\Tests\Unit\Component\Broadcaster\Notification;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Notification\BroadcastNotification;
use App\Component\WebSocket\Recipient\WebSocketRecipient;
use App\Component\WebSocket\Recipient\WebSocketRecipientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class BroadcastNotificationTest extends TestCase
{
    public function testAsChatMessageReturnsChatMessage(): void
    {
        $data = ['id' => '123', 'title' => 'Test'];
        $notification = new BroadcastNotification($data, BroadcastEvent::Created);
        $recipient = new WebSocketRecipient('noto-notes:events');

        $chatMessage = $notification->asChatMessage($recipient);

        $this->assertInstanceOf(ChatMessage::class, $chatMessage);
    }

    public function testChatMessageContainsChannelAndData(): void
    {
        $data = ['id' => '123', 'title' => 'Test'];
        $notification = new BroadcastNotification($data, BroadcastEvent::Updated);
        $recipient = new WebSocketRecipient('noto-notes:events');

        $chatMessage = $notification->asChatMessage($recipient);

        $this->assertNotNull($chatMessage);
        $this->assertSame('noto-notes:events', $chatMessage->getRecipientId());
        $this->assertSame($data, $chatMessage->getOptions()?->toArray());
    }

    public function testAsChatMessageReturnsNullForNonWebSocketRecipient(): void
    {
        $notification = new BroadcastNotification(['test' => 'data'], BroadcastEvent::Created);
        $recipient = $this->createStub(RecipientInterface::class);

        $this->assertNull($notification->asChatMessage($recipient));
    }

    public function testAsChatMessageDoesNotReturnNullForWebSocketRecipientInterface(): void
    {
        $notification = new BroadcastNotification([], BroadcastEvent::Created);
        $recipient = $this->createStub(WebSocketRecipientInterface::class);
        $recipient->method('getChannel')->willReturn('chan');

        $chatMessage = $notification->asChatMessage($recipient);

        $this->assertNotNull($chatMessage);
    }

    public function testAsChatMessageDoesNotReturnNullForWebSocketRecipient(): void
    {
        $notification = new BroadcastNotification([], BroadcastEvent::Created);
        $recipient = new WebSocketRecipient('chan');

        $chatMessage = $notification->asChatMessage($recipient);

        $this->assertNotNull($chatMessage);
    }
}
