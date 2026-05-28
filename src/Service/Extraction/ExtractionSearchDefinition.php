<?php

namespace App\Service\Extraction;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Extraction;

final class ExtractionSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Extraction::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addFilter('status', [FilterOperator::Eq, FilterOperator::In, FilterOperator::NotIn]);
        $config->addFilter('targetType', [FilterOperator::Eq, FilterOperator::In]);
        $config->addFilter('targetParent', [FilterOperator::Eq]);

        $config->addSortable('createdAt');
        $config->addSortable('updatedAt');
        $config->addSortable('status');

        $config->paginable();
    }
}
