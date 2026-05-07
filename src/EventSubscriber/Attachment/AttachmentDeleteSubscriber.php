<?php

namespace App\EventSubscriber\Attachment;

use App\Event\Attachment\AttachmentEvent;
use App\Message\Attachment\DeleteFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AttachmentDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AttachmentEvent::Deleted => '__invoke',
        ];
    }

    public function __invoke(AttachmentEvent $event): void
    {
        $this->bus->dispatch(new DeleteFile(
            $event->attachment->path,
            $event->attachment->id,
        ));
    }
}
