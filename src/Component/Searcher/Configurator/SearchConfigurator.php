<?php

namespace App\Component\Searcher\Configurator;

use App\Component\Searcher\Definition\FilterDefinition;
use App\Component\Searcher\Definition\SortDefinition;
use App\Component\Searcher\Enum\OperatorInterface;

/**
 * Configurator for defining searchable fields and pagination constraints.
 *
 * Used within SearchDefinition::configure() to declare:
 * - Allowed filters and their operators
 * - Allowed sortable fields
 * - Pagination constraints
 *
 * Field names use the AbstractDefinition structure:
 * - name: API field name (what clients use)
 * - property: entity property/database column name (what gets used in queries)
 */
class SearchConfigurator
{
    /** @var array<string, FilterDefinition> Allowed filterable fields indexed by API name */
    private array $filterDefinitions = [];

    /** @var array<string, SortDefinition> Allowed sortable fields indexed by API name */
    private array $sortDefinitions = [];

    /** Maximum records per request (0 = unlimited) */
    private int $maxLimit = 100;

    /** Default limit if not specified by client */
    private int $defaultLimit = 20;

    /**
     * Register a filterable field with allowed operators.
     *
     * @param string                   $name      The API field name (what clients send)
     * @param array<OperatorInterface> $operators List of allowed operators
     *
     * @return FilterDefinition The filter definition (for further configuration if needed)
     */
    public function addFilter(string $name, array $operators = []): FilterDefinition
    {
        $definition = new FilterDefinition($name, $operators);
        $this->filterDefinitions[$name] = $definition;

        return $definition;
    }

    /**
     * Register a sortable field.
     *
     * @param string $name The API field name (what clients send)
     *
     * @return SortDefinition The sort definition (for further configuration if needed)
     */
    public function addSortable(string $name): SortDefinition
    {
        $definition = new SortDefinition($name);
        $this->sortDefinitions[$name] = $definition;

        return $definition;
    }

    /**
     * Set pagination constraints for this entity.
     *
     * @param int $maxLimit     Maximum limit allowed (0 = no hard limit)
     * @param int $defaultLimit Default limit if not specified by client
     *
     * @return $this
     */
    public function setPaginationLimits(int $maxLimit, int $defaultLimit): self
    {
        $this->maxLimit = $maxLimit;
        $this->defaultLimit = $defaultLimit;

        return $this;
    }

    /**
     * Check if a field has been configured as filterable.
     */
    public function isFilterAllowed(string $name): bool
    {
        return isset($this->filterDefinitions[$name]);
    }

    /**
     * Check if a field has been configured as sortable.
     */
    public function isSortAllowed(string $name): bool
    {
        return isset($this->sortDefinitions[$name]);
    }

    /**
     * Get the filter definition for an API field name.
     */
    public function getFilterDefinition(string $name): ?FilterDefinition
    {
        return $this->filterDefinitions[$name] ?? null;
    }

    /**
     * Get the sort definition for an API field name.
     */
    public function getSortDefinition(string $name): ?SortDefinition
    {
        return $this->sortDefinitions[$name] ?? null;
    }

    /**
     * Get all registered filter definitions.
     *
     * @return array<string, FilterDefinition>
     */
    public function getFilterDefinitions(): array
    {
        return $this->filterDefinitions;
    }

    /**
     * Get all registered sort definitions.
     *
     * @return array<string, SortDefinition>
     */
    public function getSortDefinitions(): array
    {
        return $this->sortDefinitions;
    }

    public function getMaxLimit(): int
    {
        return $this->maxLimit;
    }

    public function getDefaultLimit(): int
    {
        return $this->defaultLimit;
    }
}
