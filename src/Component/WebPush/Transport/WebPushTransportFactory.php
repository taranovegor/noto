<?php

namespace App\Component\WebPush\Transport;

use BenTools\WebPushBundle\Sender\PushMessageSender;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebPushTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        private readonly PushMessageSender $sender,
        ?EventDispatcherInterface $dispatcher = null,
        ?HttpClientInterface $client = null,
    ) {
        parent::__construct($dispatcher, $client);
    }

    protected function getSupportedSchemes(): array
    {
        return [WebPushTransport::SCHEME];
    }

    public function create(Dsn $dsn): WebPushTransport
    {
        $scheme = $dsn->getScheme();

        if (WebPushTransport::SCHEME !== $scheme) {
            throw new UnsupportedSchemeException($dsn, WebPushTransport::SCHEME, $this->getSupportedSchemes());
        }

        return new WebPushTransport($this->sender, null, $this->dispatcher);
    }
}
