<?php

namespace App\Tests\Unit\Component\WebPush\Recipient;

use App\Component\WebPush\Recipient\WebPushRecipient;
use App\Component\WebPush\Recipient\WebPushRecipientInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class WebPushRecipientTest extends TestCase
{
    private UserSubscriptionInterface $subscription;

    protected function setUp(): void
    {
        $this->subscription = $this->createStub(UserSubscriptionInterface::class);
    }

    public function testImplementsRecipientInterface(): void
    {
        $recipient = new WebPushRecipient($this->subscription);

        $this->assertInstanceOf(RecipientInterface::class, $recipient);
    }

    public function testImplementsWebPushRecipientInterface(): void
    {
        $recipient = new WebPushRecipient($this->subscription);

        $this->assertInstanceOf(WebPushRecipientInterface::class, $recipient);
    }

    public function testGetSubscriptionReturnsConstructorValue(): void
    {
        $recipient = new WebPushRecipient($this->subscription);

        $this->assertSame($this->subscription, $recipient->getSubscription());
    }

    public function testMultipleInstancesWithDifferentSubscriptions(): void
    {
        $sub1 = $this->createConfiguredStub(UserSubscriptionInterface::class, ['getSubscriptionHash' => 'hash1']);
        $sub2 = $this->createConfiguredStub(UserSubscriptionInterface::class, ['getSubscriptionHash' => 'hash2']);

        $recipient1 = new WebPushRecipient($sub1);
        $recipient2 = new WebPushRecipient($sub2);

        $this->assertNotSame($recipient1->getSubscription(), $recipient2->getSubscription());
    }
}
