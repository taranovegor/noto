<?php

namespace App\Tests\Unit\Component\WebPush\Event;

use App\Component\WebPush\Event\WebPushSubscriptionExpired;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use PHPUnit\Framework\TestCase;

class WebPushSubscriptionExpiredTest extends TestCase
{
    public function testConstructorStoresSubscription(): void
    {
        $subscription = $this->createStub(UserSubscriptionInterface::class);

        $event = new WebPushSubscriptionExpired($subscription);

        $this->assertSame($subscription, $event->subscription);
    }

    public function testSubscriptionIsPublicReadonly(): void
    {
        $subscription = $this->createStub(UserSubscriptionInterface::class);

        $event = new WebPushSubscriptionExpired($subscription);

        $this->assertSame($subscription, $event->subscription);
    }
}
