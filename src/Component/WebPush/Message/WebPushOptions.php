<?php

namespace App\Component\WebPush\Message;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;

class WebPushOptions implements MessageOptionsInterface
{
    public function __construct(
        private readonly UserSubscriptionInterface $subscription,
        private ?string $body = null,
        private ?string $icon = null,
        private ?string $image = null,
        private ?string $badge = null,
        private mixed $vibrate = null,
        private ?string $sound = null,
        private ?string $dir = null,
        private ?string $tag = null,
        private mixed $data = null,
        private ?bool $requireInteraction = null,
        private ?bool $renotify = null,
        private ?bool $silent = null,
        private mixed $actions = null,
        private mixed $timestamp = null,
        private ?string $link = null,
    ) {
    }

    public function getSubscription(): UserSubscriptionInterface
    {
        return $this->subscription;
    }

    public function getBody(): ?string
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

    public function requireInteraction(bool $requireInteraction): static
    {
        $this->requireInteraction = $requireInteraction;

        return $this;
    }

    public function getRenotify(): ?bool
    {
        return $this->renotify;
    }

    public function renotify(bool $renotify): static
    {
        $this->renotify = $renotify;

        return $this;
    }

    public function getSilent(): ?bool
    {
        return $this->silent;
    }

    public function silent(bool $silent): static
    {
        $this->silent = $silent;

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

    public function getRecipientId(): ?string
    {
        return $this->subscription->getSubscriptionHash();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ('subscription' === $key || 'link' === $key || 'data' === $key || null === $value) {
                continue;
            }
            $options[$key] = $value;
        }

        $meta = [];
        $payload = \is_array($this->data) ? $this->data : [];

        if (null !== $this->link) {
            $meta['link'] = $this->link;
        }

        if ([] !== $meta || [] !== $payload) {
            $options['data'] = [];

            if ([] !== $meta) {
                $options['data']['meta'] = $meta;
            }
            if ([] !== $payload) {
                $options['data']['payload'] = $payload;
            }
        }

        return $options;
    }
}
