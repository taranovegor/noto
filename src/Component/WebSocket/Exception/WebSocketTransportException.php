<?php

namespace App\Component\WebSocket\Exception;

use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

final class WebSocketTransportException extends \RuntimeException implements TransportExceptionInterface
{
    public function getDebug(): string
    {
        return (string) null;
    }
}
