<?php

namespace App\Component\Centrifugo\Transport;

use App\Component\Centrifugo\Centrifugo;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CentrifugoTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        private readonly Centrifugo $centrifugo,
        ?EventDispatcherInterface $dispatcher = null,
        ?HttpClientInterface $client = null,
    ) {
        parent::__construct($dispatcher, $client);
    }

    protected function getSupportedSchemes(): array
    {
        return [CentrifugoTransport::SCHEME];
    }

    public function create(Dsn $dsn): CentrifugoTransport
    {
        $scheme = $dsn->getScheme();

        if (CentrifugoTransport::SCHEME !== $scheme) {
            throw new UnsupportedSchemeException($dsn, CentrifugoTransport::SCHEME, $this->getSupportedSchemes());
        }

        return new CentrifugoTransport($this->centrifugo, null, $this->dispatcher);
    }
}
