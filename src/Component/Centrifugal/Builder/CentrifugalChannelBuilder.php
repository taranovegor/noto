<?php

namespace App\Component\Centrifugal\Builder;

class CentrifugalChannelBuilder
{
    private ?string $namespace = null;
    private ?string $channelName = null;
    /** @var array<int, int|string> */
    private array $userIds = [];
    private bool $private = false;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): self
    {
        $this->namespace = null;
        $this->channelName = null;
        $this->userIds = [];
        $this->private = false;

        return $this;
    }

    public function private(): static
    {
        $this->private = true;

        return $this;
    }

    public function public(): static
    {
        $this->private = false;

        return $this;
    }

    public function namespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function channel(string $name): static
    {
        $this->channelName = $name;

        return $this;
    }

    public function forUser(string|int ...$userIds): static
    {
        $this->userIds = array_map('strval', $userIds);

        return $this;
    }

    public function build(): string
    {
        if (null === $this->channelName) {
            throw new \LogicException('Channel name is required');
        }

        $this->validate();

        $channel = '';

        if ($this->private) {
            $channel .= '$';
        }

        if (null !== $this->namespace) {
            $channel .= $this->namespace.':';
        }

        $channel .= $this->channelName;

        if (!empty($this->userIds)) {
            $channel .= '#'.implode(',', $this->userIds);
        }

        return $channel;
    }

    private function validate(): void
    {
        $reserved = [':', '#', '$', '/', '*', '&'];

        if (null !== $this->namespace) {
            if (!preg_match('/^[-a-zA-Z0-9_]{2,}$/', $this->namespace)) {
                throw new \InvalidArgumentException("Invalid namespace name: '{$this->namespace}'");
            }
        }

        foreach ($reserved as $symbol) {
            if (str_contains($this->channelName, $symbol)) {
                throw new \InvalidArgumentException("Channel name must not contain reserved symbol '{$symbol}'");
            }
        }

        if (!mb_check_encoding($this->channelName, 'ASCII')) {
            throw new \InvalidArgumentException('Channel name must contain only ASCII characters');
        }

        $result = ($this->private ? '$' : '')
            .($this->namespace ? $this->namespace.':' : '')
            .$this->channelName
            .(!empty($this->userIds) ? '#'.implode(',', $this->userIds) : '');

        if (strlen($result) > 255) {
            throw new \InvalidArgumentException('Channel name exceeds 255 characters');
        }
    }
}
