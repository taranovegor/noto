<?php

namespace App\EventSubscriber\Extraction;

use App\Event\Extraction\ExtractionEvent;
use App\Message\Extraction\ProcessExtraction;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ExtractionCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ExtractionEvent::Created => '__invoke'];
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ExtractionEvent $event): void
    {
        $this->messageBus->dispatch(new ProcessExtraction($event->extraction->id));
    }
}
