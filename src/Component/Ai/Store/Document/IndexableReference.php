<?php

namespace App\Component\Ai\Store\Document;

readonly class IndexableReference implements \Stringable
{
    /**
     * @param class-string $objectClass
     */
    public function __construct(
        public string $objectClass,
        public int|string $objectId,
    ) {
    }

    public static function fromString(string $reference): self
    {
        if (!str_contains($reference, '@')) {
            throw new \InvalidArgumentException(sprintf('Invalid source "%s", expected "ClassName@uuid" format.', $reference));
        }

        [$entityClass, $entityId] = explode('@', $reference, 2);

        if (!class_exists($entityClass)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" in source "%s" does not exist.', $entityClass, $reference));
        }

        return new self($entityClass, $entityId);
    }

    public function __toString(): string
    {
        return $this->objectClass.'@'.$this->objectId;
    }
}
