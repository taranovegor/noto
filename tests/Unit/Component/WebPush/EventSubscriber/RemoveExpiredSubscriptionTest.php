<?php

namespace App\Tests\Unit\Component\WebPush\EventSubscriber;

use App\Component\WebPush\Event\WebPushSubscriptionExpired;
use App\Component\WebPush\EventSubscriber\RemoveExpiredSubscription;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoveExpiredSubscriptionTest extends TestCase
{
    public function testImplementsEventSubscriberInterface(): void
    {
        $this->assertTrue(is_subclass_of(RemoveExpiredSubscription::class, EventSubscriberInterface::class));
    }

    public function testSubscribesToExpiredEvent(): void
    {
        $events = RemoveExpiredSubscription::getSubscribedEvents();

        $this->assertArrayHasKey(WebPushSubscriptionExpired::class, $events);
        $this->assertSame('__invoke', $events[WebPushSubscriptionExpired::class]);
    }
}
