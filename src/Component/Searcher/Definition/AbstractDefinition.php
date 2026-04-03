<?php

namespace App\Component\Searcher\Definition;

abstract class AbstractDefinition
{
    protected ?string $property = null;

    public function __construct(
        protected readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function setProperty(string $property): self
    {
        $this->property = $property;

        return $this;
    }
}
