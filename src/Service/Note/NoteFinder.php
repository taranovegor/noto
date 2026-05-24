<?php

namespace App\Service\Note;

use App\Component\Searcher\Dto\SearchableInterface;
use App\Component\Searcher\Dto\SearchCriteriaDecorator;
use App\Component\Searcher\Enum\FilterOperator;
use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\SearchResult;
use App\Component\Searcher\SearcherInterface;
use App\Entity\Note;
use App\Entity\Notebook;

/**
 * @template T of Note
 */
final readonly class NoteFinder
{
    /** @param SearcherInterface<Note> $searcher */
    public function __construct(
        private SearcherInterface $searcher,
    ) {
    }

    /**
     * @return SearchResult<Note>
     */
    public function inNotebook(Notebook $notebook, SearchableInterface $query): SearchResult
    {
        return $this->searcher->search(
            SearchCriteriaDecorator::wrap($query)
                ->withFilter(new FilterCondition('notebookId', FilterOperator::Eq, $notebook->id->toRfc4122())),
        );
    }
}
