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
        $config->addSortable('created_at')->setProperty('createdAt');
        $config->addSortable('id');
    }
}
