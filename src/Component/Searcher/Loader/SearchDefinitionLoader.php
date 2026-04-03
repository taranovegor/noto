<?php

namespace App\Component\Searcher\Loader;

use App\Component\Searcher\Attribute\Searchable;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * Loads SearchDefinition from DTO class using Searchable attribute.
 *
 * Resolves the definition class specified in #[Searchable(definition: ...)]
 * and fetches it from the service container.
 */
final readonly class SearchDefinitionLoader
{
    public function __construct(
        #[AutowireLocator(SearchableDefinitionInterface::class)]
        private ContainerInterface $definitionContainer,
    ) {
    }

    /**
     * Load SearchDefinition for a DTO class.
     *
     * @param class-string $dtoClass
     *
     * @throws \RuntimeException if Searchable attribute is missing or definition not found
     */
    public function load(string $dtoClass): SearchableDefinitionInterface
    {
        $reflection = new \ReflectionClass($dtoClass);
        $attributes = $reflection->getAttributes(Searchable::class);

        if (empty($attributes)) {
            throw new \RuntimeException(sprintf('DTO %s must have #[Searchable] attribute pointing to SearchDefinition', $dtoClass));
        }

        $attribute = $attributes[0]->newInstance();
        $definitionClass = $attribute->definition;

        if (!$this->definitionContainer->has($definitionClass)) {
            throw new \RuntimeException(sprintf('SearchDefinition %s not found in service container', $definitionClass));
        }

        $definition = $this->definitionContainer->get($definitionClass);

        if (!$definition instanceof SearchableDefinitionInterface) {
            throw new \RuntimeException(sprintf('%s must implement SearchableDefinitionInterface', $definitionClass));
        }

        return $definition;
    }
}
