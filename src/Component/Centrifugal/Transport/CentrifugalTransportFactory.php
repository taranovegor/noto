<?php

namespace App\Component\Centrifugal\Transport;

use App\Component\Centrifugal\Centrifugal;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CentrifugalTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        private readonly Centrifugal $centrifugal,
        ?EventDispatcherInterface $dispatcher = null,
        ?HttpClientInterface $client = null,
    ) {
        parent::__construct($dispatcher, $client);
    }

    protected function getSupportedSchemes(): array
    {
        return [CentrifugalTransport::SCHEME];
    }

    public function create(Dsn $dsn): CentrifugalTransport
    {
        $scheme = $dsn->getScheme();

        if (CentrifugalTransport::SCHEME !== $scheme) {
            throw new UnsupportedSchemeException($dsn, CentrifugalTransport::SCHEME, $this->getSupportedSchemes());
        }

        return new CentrifugalTransport($this->centrifugal, null, $this->dispatcher);
    }
}
