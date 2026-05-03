<?php

namespace App\Tests\Unit\Component\Centrifugal\Transport;

use App\Component\Centrifugal\Centrifugal;
use App\Component\Centrifugal\Service\UserIdNormalizer;
use App\Component\Centrifugal\Transport\CentrifugalTransport;
use App\Component\Centrifugal\Transport\CentrifugalTransportFactory;
use phpcent\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\Dsn;

class CentrifugalTransportFactoryTest extends TestCase
{
    private Centrifugal $centrifugal;
    private CentrifugalTransportFactory $factory;

    protected function setUp(): void
    {
        $mockClient = $this->createStub(Client::class);
        $mockLogger = $this->createStub(LoggerInterface::class);
        $userIdNormalizer = new UserIdNormalizer();

        $this->centrifugal = new Centrifugal($mockClient, $mockLogger, $userIdNormalizer);
        $this->factory = new CentrifugalTransportFactory($this->centrifugal);
    }

    public function testCreateReturnsCentrifugalTransport(): void
    {
        $dsn = new Dsn('centrifugal://localhost:8000');

        $transport = $this->factory->create($dsn);

        $this->assertInstanceOf(CentrifugalTransport::class, $transport);
    }

    public function testCreateWithValidCentrifugalScheme(): void
    {
        $dsn = new Dsn('centrifugal://api.example.com:8000');

        $transport = $this->factory->create($dsn);

        $this->assertInstanceOf(CentrifugalTransport::class, $transport);
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

    public function testSupportsReturnsTrueForValidCentrifugalDsn(): void
    {
        $dsn = new Dsn('centrifugal://localhost:8000');

        $this->assertTrue($this->factory->supports($dsn));
    }

    public function testSupportsReturnsTrueForCentrifugalScheme(): void
    {
        $dsn = new Dsn('centrifugal://localhost:8000');

        $this->assertTrue($this->factory->supports($dsn));
    }

    public function testSupportsReturnsFalseForOtherScheme(): void
    {
        $dsn = new Dsn('slack://webhook.slack.com');

        $this->assertFalse($this->factory->supports($dsn));
    }
}
