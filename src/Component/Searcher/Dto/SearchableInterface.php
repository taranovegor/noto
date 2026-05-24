<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;

/**
 * Request-side contract accepted by SearcherInterface::search().
 *
 * Carries the raw filters, sorting and pagination the client asked for, plus the FQCN of the
 * SearchDefinition that decides what is actually allowed. Which capabilities are honoured is a
 * property of the SearchDefinition, not of this type: undeclared filters/sorts are dropped and
 * pagination is applied only when the definition opts in via SearchConfigurator::paginate().
 *
 * The default implementation is SearchQuery, bound to arguments through the #[MapSearch] attribute.
 *
 * @see SearcherInterface
 * @see SearchQuery
 * @see MapSearch
 */
interface SearchableInterface
{
    /**
     * Returns the FQCN of the SearchDefinition that configures filters, sorts and pagination.
     */
    public function getSearchDefinitionClass(): string;

    /**
     * @return FilterCondition[]
     */
    public function getFilters(): array;

    /**
     * @return SortInstruction[]
     */
    public function getSorting(): array;

    public function getPagination(): PaginationDetails;
}
