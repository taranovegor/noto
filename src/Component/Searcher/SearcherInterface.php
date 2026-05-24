<?php

namespace App\Component\Searcher;

use App\Component\Searcher\Dto\SearchableInterface;
use App\Component\Searcher\Model\SearchResult;

/**
 * @template T
 */
interface SearcherInterface
{
    /**
     * @return SearchResult<T>
     */
    public function search(SearchableInterface $searchable): SearchResult;
}
