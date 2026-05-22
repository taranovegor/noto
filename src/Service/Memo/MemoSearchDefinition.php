<?php

namespace App\Service\Memo;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Memo;
use App\Service\Embedding\EmbeddingVectorFilterHandler;
use App\Service\Embedding\FilterInputVectorizer;

final class MemoSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Memo::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addFilter('query', [FilterOperator::Like])
            ->setInputTransformer(FilterInputVectorizer::class)
            ->setHandler(EmbeddingVectorFilterHandler::class);

        $config->addSortable('createdAt');
        $config->addSortable('updatedAt');
        $config->addSortable('id');
    }
}
