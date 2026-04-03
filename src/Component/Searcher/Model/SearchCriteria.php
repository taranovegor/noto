<?php

namespace App\Component\Searcher\Model;

use App\Component\Searcher\Enum\SortDirection;

/**
 * Immutable POPO containing validated search instructions for the search engine.
 *
 * Contains only fields and values explicitly allowed by SearchDefinition,
 * with database field names already mapped via entity properties.
 */
final class SearchCriteria
{
    /**
     * @param string                       $entityClass The class of the entity being searched
     * @param array<string, mixed>         $filters     Mapped filter conditions ['entity_field' => value, ...]
     * @param array<string, SortDirection> $sorting     Mapped sort instructions ['entity_field' => SortDirection, ...]
     * @param int|null                     $limit       Maximum number of records (null if unbounded)
     * @param int                          $offset      Number of records to skip
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly array $filters,
        private readonly array $sorting,
        private readonly ?int $limit,
        private readonly int $offset,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /** @return array<string, mixed> */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @return array<string, SortDirection> */
    public function getSorting(): array
    {
        return $this->sorting;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    public function hasSorting(): bool
    {
        return !empty($this->sorting);
    }

    public function hasPagination(): bool
    {
        return null !== $this->limit;
    }
}
