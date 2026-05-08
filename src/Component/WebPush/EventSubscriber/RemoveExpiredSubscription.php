<?php

namespace App\Component\WebPush\EventSubscriber;

use App\Component\WebPush\Event\WebPushSubscriptionExpired;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class RemoveExpiredSubscription implements EventSubscriberInterface
{
    public function __construct(
        private UserSubscriptionManagerRegistry $registry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [WebPushSubscriptionExpired::class => '__invoke'];
    }

    public function __invoke(WebPushSubscriptionExpired $event): void
    {
        $this->registry->delete($event->subscription);
    }
}
