<?php

namespace App\Tests\Unit\Component\WebPush\Transport;

use App\Component\WebPush\Transport\WebPushTransport;
use App\Component\WebPush\Transport\WebPushTransportFactory;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\Dsn;

class WebPushTransportFactoryTest extends TestCase
{
    private PushMessageSender $mockSender;
    private WebPushTransportFactory $factory;

    protected function setUp(): void
    {
        $this->mockSender = $this->createStub(PushMessageSender::class);
        $this->factory = new WebPushTransportFactory($this->mockSender);
    }

    public function testCreateReturnsWebPushTransport(): void
    {
        $dsn = new Dsn('webpush://push');

        $transport = $this->factory->create($dsn);

        $this->assertInstanceOf(WebPushTransport::class, $transport);
    }

    public function testCreateWithValidWebPushScheme(): void
    {
        $dsn = new Dsn('webpush://push.example.com');

        $transport = $this->factory->create($dsn);

        $this->assertInstanceOf(WebPushTransport::class, $transport);
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

    public function testCreateThrowsExceptionForCentrifugoScheme(): void
    {
        $dsn = new Dsn('centrifugo://localhost:8000');

        $this->expectException(UnsupportedSchemeException::class);
        $this->factory->create($dsn);
    }

    public function testSupportsReturnsTrueForWebPushDsn(): void
    {
        $dsn = new Dsn('webpush://push');

        $this->assertTrue($this->factory->supports($dsn));
    }

    public function testSupportsReturnsFalseForOtherScheme(): void
    {
        $dsn = new Dsn('slack://webhook.slack.com');

        $this->assertFalse($this->factory->supports($dsn));
    }

    public function testSupportsReturnsFalseForCentrifugoScheme(): void
    {
        $dsn = new Dsn('centrifugo://localhost:8000');

        $this->assertFalse($this->factory->supports($dsn));
    }
}
