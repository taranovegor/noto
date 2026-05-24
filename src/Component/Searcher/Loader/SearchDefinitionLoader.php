<?php

namespace App\Component\Searcher\Loader;

use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * Fetches a SearchDefinition instance from the service container by its class.
 *
 * The argument → definition mapping (the #[MapSearch] attribute) is resolved by the
 * value resolvers; by the time a definition class reaches the loader it is the
 * canonical FQCN returned by SearchableInterface::getSearchDefinitionClass().
 */
final readonly class SearchDefinitionLoader
{
    public function __construct(
        #[AutowireLocator(SearchableDefinitionInterface::class)]
        private ContainerInterface $definitionContainer,
    ) {
    }

    /**
     * @param class-string $definitionClass
     *
     * @throws \RuntimeException if the definition is not registered or has the wrong type
     */
    public function load(string $definitionClass): SearchableDefinitionInterface
    {
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
