<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;

/**
 * Composes a client-provided SearchableInterface with server-forced filters,
 * sorting overrides, and pagination.
 *
 * The original DTO is not modified — extra conditions are merged on access.
 */
final class SearchCriteriaDecorator implements SearchableInterface
{
    /**
     * @param FilterCondition[] $extraFilters
     * @param SortInstruction[] $extraSorting
     */
    private function __construct(
        private readonly SearchableInterface $inner,
        private array $extraFilters = [],
        private array $extraSorting = [],
        private ?PaginationDetails $paginationOverride = null,
    ) {
    }

    public static function wrap(SearchableInterface $inner): self
    {
        return new self($inner);
    }

    public function withFilter(FilterCondition $condition): self
    {
        return new self($this->inner, [...$this->extraFilters, $condition], $this->extraSorting, $this->paginationOverride);
    }

    public function withSorting(SortInstruction $instruction): self
    {
        return new self($this->inner, $this->extraFilters, [...$this->extraSorting, $instruction], $this->paginationOverride);
    }

    public function withPagination(PaginationDetails $pagination): self
    {
        return new self($this->inner, $this->extraFilters, $this->extraSorting, $pagination);
    }

    public function getFilters(): array
    {
        return array_merge($this->inner->getFilters(), $this->extraFilters);
    }

    public function getSorting(): array
    {
        if ([] !== $this->extraSorting) {
            return $this->extraSorting;
        }

        return $this->inner->getSorting();
    }

    public function getPagination(): PaginationDetails
    {
        return $this->paginationOverride ?? $this->inner->getPagination();
    }

    public function getSearchDefinitionClass(): string
    {
        return $this->inner->getSearchDefinitionClass();
    }
}
