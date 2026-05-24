<?php

namespace App\Component\Searcher\Attribute;

/**
 * Binds a controller/handler argument to a SearchQuery resolved from the request,
 * configured by the given SearchDefinition.
 *
 * Usage:
 *   public function list(#[MapSearch(TaskSearchDefinition::class)] SearchQuery $query): Response
 *
 * @see \App\Component\Searcher\Dto\SearchQuery
 * @see \App\Component\Searcher\Definition\SearchableDefinitionInterface
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class MapSearch
{
    /**
     * @param class-string $definition FQCN of the SearchDefinition that configures filters, sorts and pagination
     */
    public function __construct(
        public string $definition,
    ) {
    }
}
