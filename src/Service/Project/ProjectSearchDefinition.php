<?php

namespace App\Service\Project;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Entity\Project;

final class ProjectSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Project::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addSortable('createdAt');
        $config->addSortable('id');

        $config->paginable();
    }
}
