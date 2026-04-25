<?php

namespace App\Component\Ai\Store\Config;

final readonly class IndexableConfig
{
    /**
     * @param array<class-string, array{identifierField: string, fields: list<string>}> $config
     */
    public function __construct(
        private array $config,
    ) {
    }

    public function has(string $entityClass): bool
    {
        return isset($this->config[$entityClass]);
    }

    public function identifierField(string $entityClass): string
    {
        return $this->getOrThrow($entityClass, 'identifierField');
    }

    /**
     * @return list<string>
     */
    public function fields(string $entityClass): array
    {
        return $this->getOrThrow($entityClass, 'fields');
    }

    /**
     * @return list<class-string>
     */
    public function classes(): array
    {
        return array_keys($this->config);
    }

    private function getOrThrow(string $entityClass, string $field): mixed
    {
        return $this->config[$entityClass][$field] ?? throw new \InvalidArgumentException(sprintf('No configuration found for entity class "%s".', $entityClass));
    }
}
