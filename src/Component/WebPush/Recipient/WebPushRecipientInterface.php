<?php

namespace App\Component\WebPush\Recipient;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

interface WebPushRecipientInterface extends RecipientInterface
{
    public function getSubscription(): UserSubscriptionInterface;
}
