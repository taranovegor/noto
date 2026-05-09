<?php

namespace App\Notification\Stash;

use App\Component\WebPush\Notification\WebPushNotification;
use App\Entity\Stash;
use App\Enum\DeepLink;
use App\Enum\StashType;

class StashCreatedNotification extends WebPushNotification
{
    public function __construct(Stash $stash)
    {
        parent::__construct(channels: ['chat']);

        $this
            ->subject('Stashes notification')
            ->silent(true)
            ->tag('stash-created')
            ->link(DeepLink::Stashes->value);

        $body = match ($stash->type) {
            StashType::Text => 'New text saved',
            StashType::File => 'New attachment uploaded',
        };

        $this->body($body);
    }
}
