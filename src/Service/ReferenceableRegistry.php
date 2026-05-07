<?php

namespace App\Service;

use App\Entity\ReferenceableInterface;
use App\Enum\RefType;

final readonly class ReferenceableRegistry
{
    /**
     * @param array<string, class-string<ReferenceableInterface>> $map
     */
    public function __construct(
        private array $map = [],
    ) {
    }

    /**
     * @return class-string<ReferenceableInterface>
     */
    public function getClass(RefType $type): string
    {
        return $this->map[$type->value] ?? throw new \LogicException(\sprintf('No entity class registered for RefType "%s".', $type->value));
    }

    public function hasClass(RefType $type): bool
    {
        return isset($this->map[$type->value]);
    }
}
