<?php

namespace App\Component\WebPush\Transport;

use App\Component\WebPush\Event\WebPushSubscriptionExpired;
use App\Component\WebPush\Exception\WebPushTransportException;
use App\Component\WebPush\Message\WebPushOptions;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebPushTransport extends AbstractTransport
{
    public const string SCHEME = 'webpush';

    public function __construct(
        private readonly PushMessageSender $sender,
        ?HttpClientInterface $client = null,
        protected readonly ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($client, $dispatcher);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && $message->getOptions() instanceof WebPushOptions;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$this->supports($message)) {
            throw new UnsupportedMessageTypeException(__CLASS__, ChatMessage::class, $message);
        }

        /** @var WebPushOptions $options */
        $options = $message->getOptions();

        $subscription = $options->getSubscription();
        $id = Uuid::v7()->toRfc4122();

        $pushOptions = $options->toArray();
        $pushOptions['data']['meta']['id'] = $id;

        $notification = new PushNotification($message->getSubject(), $pushOptions);

        try {
            $responses = $this->sender->push($notification->createMessage(), [$subscription]);
        } catch (\Throwable $e) {
            throw new WebPushTransportException($e->getMessage(), previous: $e);
        }

        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $this->dispatcher?->dispatch(
                    new WebPushSubscriptionExpired($response->getSubscription()),
                );
            }
        }

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($id);

        return $sentMessage;
    }

    public function __toString(): string
    {
        return \sprintf('%s://%s', self::SCHEME, $this->getEndpoint());
    }
}
