<?php

namespace App\Component\WebPush\Notification;

use App\Component\WebPush\Message\WebPushOptions;
use App\Component\WebPush\Recipient\WebPushRecipientInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class WebPushNotification extends Notification implements ChatNotificationInterface
{
    private string $body = '';
    private ?string $icon = null;
    private ?string $image = null;
    private ?string $badge = null;
    private mixed $vibrate = null;
    private ?string $sound = null;
    private ?string $dir = null;
    private ?string $tag = null;
    private mixed $data = null;
    private ?bool $requireInteraction = null;
    private ?bool $renotify = null;
    private ?bool $silent = null;
    private mixed $actions = null;
    private mixed $timestamp = null;
    private ?string $link = null;

    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ?ChatMessage
    {
        if (!$recipient instanceof WebPushRecipientInterface) {
            return null;
        }

        $options = new WebPushOptions(
            $recipient->getSubscription(),
            body: $this->body,
            icon: $this->icon,
            image: $this->image,
            badge: $this->badge,
            vibrate: $this->vibrate,
            sound: $this->sound,
            dir: $this->dir,
            tag: $this->tag,
            data: $this->data,
            requireInteraction: $this->requireInteraction,
            renotify: $this->renotify,
            silent: $this->silent,
            actions: $this->actions,
            timestamp: $this->timestamp,
            link: $this->link,
        );

        return new ChatMessage($this->getSubject(), $options);
    }

    public function content(string $content): static
    {
        $this->body = $content;

        return parent::content($content);
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function image(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function badge(string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function getVibrate(): mixed
    {
        return $this->vibrate;
    }

    public function vibrate(mixed $vibrate): static
    {
        $this->vibrate = $vibrate;

        return $this;
    }

    public function getSound(): ?string
    {
        return $this->sound;
    }

    public function sound(string $sound): static
    {
        $this->sound = $sound;

        return $this;
    }

    public function getDir(): ?string
    {
        return $this->dir;
    }

    public function dir(string $dir): static
    {
        $this->dir = $dir;

        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function tag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function data(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getRequireInteraction(): ?bool
    {
        return $this->requireInteraction;
    }

    public function requireInteraction(bool $v): static
    {
        $this->requireInteraction = $v;

        return $this;
    }

    public function getRenotify(): ?bool
    {
        return $this->renotify;
    }

    public function renotify(bool $v): static
    {
        $this->renotify = $v;

        return $this;
    }

    public function getSilent(): ?bool
    {
        return $this->silent;
    }

    public function silent(bool $v): static
    {
        $this->silent = $v;

        return $this;
    }

    public function getActions(): mixed
    {
        return $this->actions;
    }

    public function actions(mixed $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    public function getTimestamp(): mixed
    {
        return $this->timestamp;
    }

    public function timestamp(mixed $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function link(string $link): static
    {
        $this->link = $link;

        return $this;
    }
}
