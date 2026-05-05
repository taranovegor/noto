<?php

namespace App\Tests\Unit\Component\Centrifugo\Transport;

use App\Component\Centrifugo\Centrifugo;
use App\Component\Centrifugo\Service\UserIdNormalizer;
use App\Component\Centrifugo\Transport\CentrifugoTransport;
use App\Component\Centrifugo\Transport\CentrifugoTransportFactory;
use phpcent\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\Dsn;

class CentrifugoTransportFactoryTest extends TestCase
{
    private Centrifugo $centrifugo;
    private CentrifugoTransportFactory $factory;

    protected function setUp(): void
    {
        $mockClient = $this->createStub(Client::class);
        $mockLogger = $this->createStub(LoggerInterface::class);
        $userIdNormalizer = new UserIdNormalizer();

        $this->centrifugo = new Centrifugo($mockClient, $mockLogger, $userIdNormalizer);
        $this->factory = new CentrifugoTransportFactory($this->centrifugo);
    }

    public function testCreateReturnsCentrifugoTransport(): void
    {
        $dsn = new Dsn('centrifugo://localhost:8000');

        $transport = $this->factory->create($dsn);

        $this->assertInstanceOf(CentrifugoTransport::class, $transport);
    }

    public function testCreateWithValidCentrifugoScheme(): void
    {
        $dsn = new Dsn('centrifugo://api.example.com:8000');

        $transport = $this->factory->create($dsn);

        $this->assertInstanceOf(CentrifugoTransport::class, $transport);
    }

    public function testCreateThrowsExceptionForUnsupportedScheme(): void
    {
        $dsn = new Dsn('slack://webhook.slack.com');

        $this->expectException(UnsupportedSchemeException::class);
        $this->factory->create($dsn);
    }

    public function testCreateThrowsExceptionForHttpScheme(): void
    {
        $dsn = new Dsn('http://localhost:8000');

        $this->expectException(UnsupportedSchemeException::class);
        $this->factory->create($dsn);
    }

    public function testCreateThrowsExceptionForTelegrammScheme(): void
    {
        $dsn = new Dsn('telegram://token@default');

        $this->expectException(UnsupportedSchemeException::class);
        $this->factory->create($dsn);
    }

    public function testSupportsReturnsTrueForValidCentrifugoDsn(): void
    {
        $dsn = new Dsn('centrifugo://localhost:8000');

        $this->assertTrue($this->factory->supports($dsn));
    }

    public function testSupportsReturnsTrueForCentrifugoScheme(): void
    {
        $dsn = new Dsn('centrifugo://localhost:8000');

        $this->assertTrue($this->factory->supports($dsn));
    }

    public function testSupportsReturnsFalseForOtherScheme(): void
    {
        $dsn = new Dsn('slack://webhook.slack.com');

        $this->assertFalse($this->factory->supports($dsn));
    }
}
