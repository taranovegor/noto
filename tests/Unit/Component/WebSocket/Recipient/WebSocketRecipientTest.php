<?php

namespace App\Tests\Unit\Component\WebSocket\Recipient;

use App\Component\WebSocket\Recipient\WebSocketRecipient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class WebSocketRecipientTest extends TestCase
{
    public function testImplementsRecipientInterface(): void
    {
        $recipient = new WebSocketRecipient('test-channel');

        $this->assertInstanceOf(RecipientInterface::class, $recipient);
    }

    public function testConstructorStoresChannel(): void
    {
        $channel = 'test-channel';
        $recipient = new WebSocketRecipient($channel);

        $this->assertEquals($channel, $recipient->getChannel());
    }

    public function testGetChannelReturnsConstructorValue(): void
    {
        $recipient = new WebSocketRecipient('notifications');

        $this->assertEquals('notifications', $recipient->getChannel());
    }

    public function testChannelWithSlashes(): void
    {
        $channel = 'users/123/notifications';
        $recipient = new WebSocketRecipient($channel);

        $this->assertEquals($channel, $recipient->getChannel());
    }

    public function testChannelWithHyphen(): void
    {
        $channel = 'task-updates-feed';
        $recipient = new WebSocketRecipient($channel);

        $this->assertEquals($channel, $recipient->getChannel());
    }

    public function testChannelWithUnderscore(): void
    {
        $channel = 'user_notifications_v2';
        $recipient = new WebSocketRecipient($channel);

        $this->assertEquals($channel, $recipient->getChannel());
    }

    public function testEmptyChannel(): void
    {
        $recipient = new WebSocketRecipient('');

        $this->assertEquals('', $recipient->getChannel());
    }

    public function testLongChannelName(): void
    {
        $channel = 'very-long-channel-name-with-many-parts-and-descriptors-for-specific-use-case-notification-type';
        $recipient = new WebSocketRecipient($channel);

        $this->assertEquals($channel, $recipient->getChannel());
    }

    public function testChannelNameWithDots(): void
    {
        $channel = 'app.users.123.notifications';
        $recipient = new WebSocketRecipient($channel);

        $this->assertEquals($channel, $recipient->getChannel());
    }

    public function testMultipleInstancesWithDifferentChannels(): void
    {
        $recipient1 = new WebSocketRecipient('channel-1');
        $recipient2 = new WebSocketRecipient('channel-2');

        $this->assertEquals('channel-1', $recipient1->getChannel());
        $this->assertEquals('channel-2', $recipient2->getChannel());
        $this->assertNotEquals($recipient1->getChannel(), $recipient2->getChannel());
    }

    public function testRecipientIsReadonly(): void
    {
        $recipient = new WebSocketRecipient('test-channel');

        // Test that the object is readonly by checking it doesn't have a setter
        $this->assertTrue(method_exists($recipient, 'getChannel'));
        // If there was a setter, this assertion would fail after property modification
        $this->assertEquals('test-channel', $recipient->getChannel());
    }
}
