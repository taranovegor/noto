<?php

namespace App\Component\WebPush\Exception;

use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

final class WebPushTransportException extends \RuntimeException implements TransportExceptionInterface
{
    public function getDebug(): string
    {
        return (string) null;
    }
}
