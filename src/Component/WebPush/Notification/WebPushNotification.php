<?php

namespace App\Component\WebPush\Notification;

use App\Component\WebPush\Message\WebPushOptions;
use App\Component\WebPush\Recipient\WebPushRecipientInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class WebPushNotification extends Notification implements ChatNotificationInterface
{
    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ?ChatMessage
    {
        if (!$recipient instanceof WebPushRecipientInterface) {
            return null;
        }

        $options = new WebPushOptions($recipient->getSubscription(), $this->getContent());

        return new ChatMessage($this->getSubject(), $options);
    }
}
