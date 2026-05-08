<?php

namespace App\Component\WebPush\Event;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;

final readonly class WebPushSubscriptionExpired
{
    public function __construct(
        public UserSubscriptionInterface $subscription,
    ) {
    }
}
