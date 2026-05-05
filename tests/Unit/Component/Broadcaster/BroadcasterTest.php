<?php

namespace App\Tests\Unit\Component\Broadcaster;

use App\Component\Broadcaster\Broadcaster;
use App\Component\Broadcaster\BroadcasterInterface;
use App\Component\Broadcaster\Notification\BroadcastNotification;
use App\Component\Centrifugo\Builder\CentrifugoChannelBuilder;
use App\Component\WebSocket\Recipient\WebSocketRecipient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\NotifierInterface;

class BroadcasterTest extends TestCase
{
    public function testBroadcastSendsToCorrectChannel(): void
    {
        $notifier = $this->createMock(NotifierInterface::class);
        $broadcaster = new Broadcaster($notifier, new CentrifugoChannelBuilder(), 'noto');

        $notifier->expects($this->once())
            ->method('send')
            ->with(
                $this->isInstanceOf(BroadcastNotification::class),
                $this->callback(function (WebSocketRecipient $recipient) {
                    return 'noto-notes:events' === $recipient->getChannel();
                }),
            );

        $broadcaster->broadcast('notes', 'events', ['id' => '123']);
    }

    public function testBroadcastSendsNotificationWithData(): void
    {
        $notifier = $this->createMock(NotifierInterface::class);
        $broadcaster = new Broadcaster($notifier, new CentrifugoChannelBuilder(), 'noto');

        $data = ['id' => '019ddda6-541c-7262-91ac-f9af494e26ef', 'title' => 'Test'];

        $notifier->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (BroadcastNotification $notification) use ($data) {
                    $chatMessage = $notification->asChatMessage(new WebSocketRecipient('noto-notes:events'));

                    return $data === $chatMessage->getOptions()?->toArray();
                }),
                $this->isInstanceOf(WebSocketRecipient::class),
            );

        $broadcaster->broadcast('notes', 'events', $data);
    }

    public function testBroadcastBuildsChannelWithNamespacePrefix(): void
    {
        $notifier = $this->createMock(NotifierInterface::class);
        $broadcaster = new Broadcaster($notifier, new CentrifugoChannelBuilder(), 'noto');

        $notifier->expects($this->once())
            ->method('send')
            ->with(
                $this->anything(),
                $this->callback(function (WebSocketRecipient $recipient) {
                    return 'noto-tasks:events' === $recipient->getChannel();
                }),
            );

        $broadcaster->broadcast('tasks', 'events', []);
    }

    public function testImplementsInterface(): void
    {
        $broadcaster = new Broadcaster(
            $this->createStub(NotifierInterface::class),
            new CentrifugoChannelBuilder(),
            'noto',
        );

        $this->assertInstanceOf(BroadcasterInterface::class, $broadcaster);
    }
}
