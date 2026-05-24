<?php

namespace App\Service\Note;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Note;
use App\Service\Embedding\EmbeddingVectorFilterHandler;
use App\Service\Embedding\FilterInputVectorizer;
use Symfony\Component\Validator\Constraints as Assert;

final class NoteSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Note::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addFilter('query', [FilterOperator::Like])
            ->setInputTransformer(FilterInputVectorizer::class)
            ->setHandler(EmbeddingVectorFilterHandler::class);

        $config->addFilter('notebookId', [FilterOperator::Eq])
            ->setProperty('notebook')
            ->addConstraint(new Assert\Uuid());

        $config->addSortable('title');
        $config->addSortable('createdAt');
        $config->addSortable('updatedAt');

        $config->paginable();
    }
}
