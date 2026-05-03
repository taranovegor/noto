<?php

namespace App\Tests\Unit\Component\WebSocket\Exception;

use App\Component\WebSocket\Exception\WebSocketTransportException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class WebSocketTransportExceptionTest extends TestCase
{
    public function testImplementsTransportExceptionInterface(): void
    {
        $exception = new WebSocketTransportException();

        $this->assertInstanceOf(TransportExceptionInterface::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new WebSocketTransportException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Connection failed';
        $exception = new WebSocketTransportException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testConstructorWithCode(): void
    {
        $code = 1001;
        $exception = new WebSocketTransportException('Error', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testConstructorWithPreviousException(): void
    {
        $previous = new \RuntimeException('Root cause');
        $exception = new WebSocketTransportException('Wrapper error', previous: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGetDebugReturnsEmptyString(): void
    {
        $exception = new WebSocketTransportException('Test error');

        $this->assertEquals('', $exception->getDebug());
    }

    public function testGetDebugWithDifferentMessages(): void
    {
        $exception1 = new WebSocketTransportException('Error 1');
        $exception2 = new WebSocketTransportException('Error 2');

        $this->assertEquals('', $exception1->getDebug());
        $this->assertEquals('', $exception2->getDebug());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(WebSocketTransportException::class);

        throw new WebSocketTransportException('Test exception');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new WebSocketTransportException('Test error', 500);
        } catch (WebSocketTransportException $e) {
            $this->assertEquals('Test error', $e->getMessage());
            $this->assertEquals(500, $e->getCode());
        }
    }

    public function testExceptionCanBeCaughtAsTransportException(): void
    {
        try {
            throw new WebSocketTransportException('Transport error');
        } catch (TransportExceptionInterface $e) {
            $this->assertInstanceOf(WebSocketTransportException::class, $e);
        }
    }

    public function testFullExceptionConstructor(): void
    {
        $previous = new \Exception('Original error');
        $exception = new WebSocketTransportException(
            'WebSocket transport failed',
            1001,
            $previous
        );

        $this->assertEquals('WebSocket transport failed', $exception->getMessage());
        $this->assertEquals(1001, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('', $exception->getDebug());
    }
}
