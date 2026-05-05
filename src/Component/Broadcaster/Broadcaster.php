<?php

namespace App\Component\Broadcaster;

use App\Component\Broadcaster\Notification\BroadcastNotification;
use App\Component\Centrifugo\Builder\CentrifugoChannelBuilder;
use App\Component\WebSocket\Recipient\WebSocketRecipient;
use Symfony\Component\Notifier\NotifierInterface;

final readonly class Broadcaster implements BroadcasterInterface
{
    public function __construct(
        private NotifierInterface $notifier,
        private CentrifugoChannelBuilder $channelBuilder,
        private string $namespacePrefix,
    ) {
    }

    public function broadcast(string $namespace, string $channel, array $data): void
    {
        $channel = $this->channelBuilder->reset()
            ->public()
            ->namespace(sprintf('%s-%s', $this->namespacePrefix, $namespace))
            ->channel($channel)
            ->build();
        $this->notifier->send(new BroadcastNotification($data), new WebSocketRecipient($channel));
    }
}
