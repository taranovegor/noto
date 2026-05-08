<?php

namespace App\Component\WebPush\Recipient;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;

final readonly class WebPushRecipient implements WebPushRecipientInterface
{
    public function __construct(
        private UserSubscriptionInterface $subscription,
    ) {
    }

    public function getSubscription(): UserSubscriptionInterface
    {
        return $this->subscription;
    }
}
