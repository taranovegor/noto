<?php

namespace App\Tests\Unit\Component\WebPush\Transport;

use App\Component\WebPush\Event\WebPushSubscriptionExpired;
use App\Component\WebPush\Exception\WebPushTransportException;
use App\Component\WebPush\Message\WebPushOptions;
use App\Component\WebPush\Transport\WebPushTransport;
use BenTools\WebPushBundle\Model\Message\PushMessage;
use BenTools\WebPushBundle\Model\Response\PushResponse;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

class WebPushTransportTest extends TestCase
{
    private MockObject|PushMessageSender $mockSender;
    private UserSubscriptionInterface $subscription;
    private WebPushTransport $transport;

    protected function setUp(): void
    {
        $this->mockSender = $this->createStub(PushMessageSender::class);
        $this->transport = new WebPushTransport($this->mockSender);
        $this->subscription = $this->createConfiguredStub(
            UserSubscriptionInterface::class,
            ['getSubscriptionHash' => 'abc123'],
        );
    }

    public function testSupportsReturnsTrueForChatMessageWithWebPushOptions(): void
    {
        $options = new WebPushOptions($this->subscription);
        $message = new ChatMessage('Test subject', $options);

        $this->assertTrue($this->transport->supports($message));
    }

    public function testSupportsReturnsFalseForNonChatMessage(): void
    {
        $message = new SmsMessage('1234567890', 'Test message');

        $this->assertFalse($this->transport->supports($message));
    }

    public function testSupportsReturnsFalseForChatMessageWithoutWebPushOptions(): void
    {
        $message = new ChatMessage('Test subject');

        $this->assertFalse($this->transport->supports($message));
    }

    public function testDoSendSendsPushNotification(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $transport = new WebPushTransport($sender);

        $options = new WebPushOptions($this->subscription, body: 'Hello');
        $message = new ChatMessage('Notification title', $options);

        $sender->expects($this->once())
            ->method('push')
            ->with(
                $this->callback(function (PushMessage $pushMessage) {
                    $payload = json_decode($pushMessage->getPayload(), true);

                    return 'Notification title' === ($payload['title'] ?? null);
                }),
                [$this->subscription],
            )
            ->willReturn([]);

        $sentMessage = $transport->send($message);

        $this->assertNotNull($sentMessage->getMessageId());
    }

    public function testDoSendPassesOptionsToPushNotification(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $transport = new WebPushTransport($sender);

        $options = new WebPushOptions($this->subscription, body: 'Hello', icon: '/icon.svg');
        $message = new ChatMessage('Title', $options);

        $sender->expects($this->once())
            ->method('push')
            ->with(
                $this->callback(function (PushMessage $pushMessage) {
                    $payload = json_decode($pushMessage->getPayload(), true);
                    $opts = $payload['options'] ?? [];

                    return 'Hello' === ($opts['body'] ?? null)
                        && '/icon.svg' === ($opts['icon'] ?? null);
                }),
                $this->anything(),
            )
            ->willReturn([]);

        $transport->send($message);
    }

    public function testDoSendGeneratesUniqueMessageIds(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $transport = new WebPushTransport($sender);

        $sender->expects($this->exactly(2))
            ->method('push')
            ->willReturn([]);

        $msg1 = new ChatMessage('Subject 1', new WebPushOptions($this->subscription));
        $msg2 = new ChatMessage('Subject 2', new WebPushOptions($this->subscription));

        $sent1 = $transport->send($msg1);
        $sent2 = $transport->send($msg2);

        $this->assertNotEquals($sent1->getMessageId(), $sent2->getMessageId());
    }

    public function testDoSendThrowsExceptionOnUnsupportedMessage(): void
    {
        $message = new SmsMessage('1234567890', 'Test message');

        $this->expectException(UnsupportedMessageTypeException::class);
        $this->transport->send($message);
    }

    public function testDoSendThrowsWebPushTransportExceptionOnSenderFailure(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $transport = new WebPushTransport($sender);

        $sender->expects($this->once())
            ->method('push')
            ->willThrowException(new \RuntimeException('Push failed'));

        $message = new ChatMessage('Title', new WebPushOptions($this->subscription));

        $this->expectException(WebPushTransportException::class);
        $transport->send($message);
    }

    public function testDoSendDispatchesExpiredEvent(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $dispatcher = new EventDispatcher();
        $transport = new WebPushTransport($sender, null, $dispatcher);

        $eventFired = false;
        $dispatcher->addListener(WebPushSubscriptionExpired::class, function () use (&$eventFired) {
            $eventFired = true;
        });

        $expiredResponse = new PushResponse($this->subscription, 410);
        $sender->expects($this->once())
            ->method('push')
            ->willReturn([$expiredResponse]);

        $message = new ChatMessage('Title', new WebPushOptions($this->subscription));
        $transport->send($message);

        $this->assertTrue($eventFired);
    }

    public function testDoSendDoesNotDispatchForSuccessfulResponse(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $dispatcher = new EventDispatcher();
        $transport = new WebPushTransport($sender, null, $dispatcher);

        $eventFired = false;
        $dispatcher->addListener(WebPushSubscriptionExpired::class, function () use (&$eventFired) {
            $eventFired = true;
        });

        $successResponse = new PushResponse($this->subscription, 201);
        $sender->expects($this->once())
            ->method('push')
            ->willReturn([$successResponse]);

        $message = new ChatMessage('Title', new WebPushOptions($this->subscription));
        $transport->send($message);

        $this->assertFalse($eventFired);
    }

    public function testToStringReturnsValidScheme(): void
    {
        $stringRepresentation = (string) $this->transport;

        $this->assertStringStartsWith('webpush://', $stringRepresentation);
    }

    public function testDoSendWithLinkMergedInData(): void
    {
        $sender = $this->createMock(PushMessageSender::class);
        $transport = new WebPushTransport($sender);

        $options = new WebPushOptions($this->subscription, link: '/stashes');
        $message = new ChatMessage('Title', $options);

        $sender->expects($this->once())
            ->method('push')
            ->with(
                $this->callback(function (PushMessage $pushMessage) {
                    $payload = json_decode($pushMessage->getPayload(), true);
                    $opts = $payload['options'] ?? [];

                    return isset($opts['data']['meta']['link']) && '/stashes' === $opts['data']['meta']['link'];
                }),
                $this->anything(),
            )
            ->willReturn([]);

        $transport->send($message);
    }
}
