<?php

namespace App\Component\Searcher\Model;

/**
 * Result of a search operation containing data and optional pagination info.
 *
 * @template T
 */
final class SearchResult
{
    /**
     * @param list<T>         $data
     * @param Pagination|null $pagination null if pagination was not requested
     */
    public function __construct(
        private readonly array $data,
        private readonly ?Pagination $pagination,
    ) {
    }

    /**
     * @return list<T>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }

    /**
     * @template U
     *
     * @param callable(T): U $mapper
     *
     * @return SearchResult<U>
     */
    public function map(callable $mapper): SearchResult
    {
        return new SearchResult(array_map($mapper, $this->data), $this->pagination);
    }
}
