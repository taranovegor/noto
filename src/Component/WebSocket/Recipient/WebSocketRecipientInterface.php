<?php

namespace App\Component\WebSocket\Recipient;

use Symfony\Component\Notifier\Recipient\RecipientInterface;

interface WebSocketRecipientInterface extends RecipientInterface
{
    public function getChannel(): string;
}
