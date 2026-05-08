<?php

namespace App\Tests\Unit\Component\WebPush\Notification;

use App\Component\WebPush\Message\WebPushOptions;
use App\Component\WebPush\Notification\WebPushNotification;
use App\Component\WebPush\Recipient\WebPushRecipient;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class WebPushNotificationTest extends TestCase
{
    private UserSubscriptionInterface $subscription;

    protected function setUp(): void
    {
        $this->subscription = $this->createConfiguredStub(
            UserSubscriptionInterface::class,
            ['getSubscriptionHash' => 'abc123'],
        );
    }

    public function testAsChatMessageReturnsChatMessage(): void
    {
        $notification = new WebPushNotification('Hi!');
        $recipient = new WebPushRecipient($this->subscription);

        $result = $notification->asChatMessage($recipient);

        $this->assertInstanceOf(ChatMessage::class, $result);
    }

    public function testAsChatMessageUsesNotificationSubject(): void
    {
        $notification = new WebPushNotification('Check this stash');
        $recipient = new WebPushRecipient($this->subscription);

        $chatMessage = $notification->asChatMessage($recipient);

        $this->assertSame('Check this stash', $chatMessage->getSubject());
    }

    public function testAsChatMessagePassesSubscriptionToOptions(): void
    {
        $notification = new WebPushNotification('Title');
        $recipient = new WebPushRecipient($this->subscription);

        $chatMessage = $notification->asChatMessage($recipient);
        $options = $chatMessage->getOptions();

        $this->assertInstanceOf(WebPushOptions::class, $options);
        $this->assertSame($this->subscription, $options->getSubscription());
    }

    public function testAsChatMessageReturnsNullForInvalidRecipient(): void
    {
        $notification = new WebPushNotification('Title');
        $recipient = $this->createStub(RecipientInterface::class);

        $this->assertNull($notification->asChatMessage($recipient));
    }

    public function testAsChatMessagePassesContentAsBody(): void
    {
        $notification = new WebPushNotification('Title');
        $notification->content('Stash updated successfully');
        $recipient = new WebPushRecipient($this->subscription);

        $chatMessage = $notification->asChatMessage($recipient);
        $options = $chatMessage->getOptions();

        $this->assertInstanceOf(WebPushOptions::class, $options);
        $this->assertSame('Stash updated successfully', $options->getBody());
    }

    public function testAsChatMessageWithoutContent(): void
    {
        $notification = new WebPushNotification('Title');
        $recipient = new WebPushRecipient($this->subscription);

        $chatMessage = $notification->asChatMessage($recipient);
        $options = $chatMessage->getOptions();

        $this->assertInstanceOf(WebPushOptions::class, $options);
        $this->assertSame('', $options->getBody());
    }

    public function testImplementsChatNotificationInterface(): void
    {
        $this->assertTrue(method_exists(WebPushNotification::class, 'asChatMessage'));
    }
}
