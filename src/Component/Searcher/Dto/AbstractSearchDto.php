<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;

abstract readonly class AbstractSearchDto implements FilterableInterface, SortableInterface, PaginableInterface
{
    /**
     * @param FilterCondition[] $filters
     * @param SortInstruction[] $sorting
     */
    public function __construct(
        private array $filters,
        private array $sorting,
        private PaginationDetails $pagination,
    ) {
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
