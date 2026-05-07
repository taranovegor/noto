<?php

namespace App\Service;

use App\Enum\RefType;
use Symfony\Contracts\EventDispatcher\Event;

final readonly class ReferenceableEventRegistry
{
    /**
     * @param array<string, class-string<Event>> $map
     */
    public function __construct(
        private array $map = [],
    ) {
    }

    /**
     * @return class-string<Event>
     */
    public function getClass(RefType $type): string
    {
        return $this->map[$type->value] ?? throw new \LogicException(\sprintf('No event class registered for RefType "%s".', $type->value));
    }

    public function hasClass(RefType $type): bool
    {
        return isset($this->map[$type->value]);
    }
}
