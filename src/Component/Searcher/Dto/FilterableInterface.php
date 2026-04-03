<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\FilterCondition;

interface FilterableInterface extends SearchableInterface
{
    /**
     * @return FilterCondition[]
     */
    public function getFilters(): array;
}
