<?php

namespace App\Component\Searcher;

use App\Component\Searcher\Dto\FilterableInterface;
use App\Component\Searcher\Dto\PaginableInterface;
use App\Component\Searcher\Dto\SortableInterface;
use App\Component\Searcher\Model\SearchResult;

/**
 * @template T
 */
interface SearcherInterface
{
    /**
     * @return SearchResult<T>
     */
    public function search(FilterableInterface|SortableInterface|PaginableInterface $searchable): SearchResult;
}
