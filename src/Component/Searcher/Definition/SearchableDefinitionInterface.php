<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Configurator\SearchConfigurator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface SearchableDefinitionInterface
{
    /**
     * @return class-string
     */
    public function getEntityClass(): string;

    public function configure(SearchConfigurator $config): void;
}
