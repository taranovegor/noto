<?php

namespace App\Component\Centrifugal\Transport;

use App\Component\Centrifugal\CentrifugalInterface;
use App\Component\WebSocket\Exception\WebSocketTransportException;
use App\Component\WebSocket\Message\WebSocketOptions;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CentrifugalTransport extends AbstractTransport
{
    public const string SCHEME = 'centrifugal';

    public function __construct(
        private readonly CentrifugalInterface $centrifugal,
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($client, $dispatcher);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && (null === $message->getOptions() || $message->getOptions() instanceof WebSocketOptions);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$this->supports($message)) {
            throw new UnsupportedMessageTypeException(__CLASS__, ChatMessage::class, $message);
        }

        try {
            $this->centrifugal->publish(
                $message->getRecipientId(),
                [
                    'meta' => [
                        'id' => $id = Uuid::v7()->toRfc4122(),
                        'subject' => $message->getSubject(),
                    ],
                    'data' => $message->getOptions()?->toArray() ?? [],
                ],
            );
        } catch (\Throwable $e) {
            throw new WebSocketTransportException($e->getMessage(), previous: $e);
        }

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($id);

        return $sentMessage;
    }

    public function __toString(): string
    {
        return sprintf('%s://%s', self::SCHEME, $this->getEndpoint());
    }
}
