<?php

namespace App\Service\Note;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Entity\Note;

final class NoteSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Note::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addSortable('createdAt');
        $config->addSortable('updatedAt');
        $config->addSortable('id');
    }
}
