<?php

namespace App\Service\Stash;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Entity\Stash;

final class StashSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Stash::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addSortable('pinned');
        $config->addSortable('expiresAt');
        $config->addSortable('updatedAt');

        $config->paginable();
    }
}
