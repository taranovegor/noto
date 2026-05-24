<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;

/**
 * Default request-side search DTO, bound to arguments via the #[MapSearch] attribute.
 *
 * It is a dumb carrier of what the client sent; the SearchDefinition referenced by
 * $searchDefinitionClass decides which filters/sorts/pagination are actually honoured.
 *
 * Extend it only when a consumer needs a named or specialised type; otherwise use it as-is.
 */
readonly class SearchQuery implements SearchableInterface
{
    /**
     * @param FilterCondition[] $filters
     * @param SortInstruction[] $sorting
     * @param class-string      $searchDefinitionClass
     */
    public function __construct(
        private array $filters,
        private array $sorting,
        private PaginationDetails $pagination,
        private string $searchDefinitionClass,
    ) {
    }

    public function getSearchDefinitionClass(): string
    {
        return $this->searchDefinitionClass;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSorting(): array
    {
        return $this->sorting;
    }

    public function getPagination(): PaginationDetails
    {
        return $this->pagination;
    }
}
