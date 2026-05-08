<?php

namespace App\Component\Broadcaster\Config;

/**
 * @phpstan-type NamespaceMap = array<class-string, string>
 */
final readonly class BroadcastableConfig
{
    /**
     * @param NamespaceMap $config class-string → namespace
     */
    public function __construct(
        private array $config = [],
    ) {
    }

    public function getNamespace(string $entityClass): ?string
    {
        return $this->config[$entityClass] ?? null;
    }

    /**
     * @return list<class-string>
     */
    public function classes(): array
    {
        return array_keys($this->config);
    }
}
