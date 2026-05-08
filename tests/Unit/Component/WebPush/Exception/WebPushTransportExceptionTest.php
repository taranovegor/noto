<?php

namespace App\Tests\Unit\Component\WebPush\Exception;

use App\Component\WebPush\Exception\WebPushTransportException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class WebPushTransportExceptionTest extends TestCase
{
    public function testImplementsTransportExceptionInterface(): void
    {
        $exception = new WebPushTransportException();

        $this->assertInstanceOf(TransportExceptionInterface::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new WebPushTransportException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithMessage(): void
    {
        $exception = new WebPushTransportException('Push failed');

        $this->assertSame('Push failed', $exception->getMessage());
    }

    public function testConstructorWithCode(): void
    {
        $exception = new WebPushTransportException('Error', 500);

        $this->assertSame(500, $exception->getCode());
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \RuntimeException('Root cause');
        $exception = new WebPushTransportException('Wrapper', previous: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGetDebugReturnsEmptyString(): void
    {
        $exception = new WebPushTransportException('Test error');

        $this->assertSame('', $exception->getDebug());
    }
}
