<?php

namespace App\EventSubscriber\Stash;

use App\Component\WebPush\Recipient\WebPushRecipientProvider;
use App\Event\Stash\StashEvent;
use App\Notification\Stash\StashCreatedNotification;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\NotifierInterface;

final readonly class StashCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotifierInterface $notifier,
        private WebPushRecipientProvider $recipientProvider,
        private Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [StashEvent::Created => '__invoke'];
    }

    public function __invoke(StashEvent $event): void
    {
        if (!$user = $this->security->getUser()) {
            return;
        }

        $notification = new StashCreatedNotification($event->stash);

        $this->notifier->send($notification, ...$this->recipientProvider->forUserExceptCurrent($user));
    }
}
