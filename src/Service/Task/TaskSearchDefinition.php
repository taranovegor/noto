<?php

namespace App\Service\Task;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Service\Embedding\EmbeddingVectorFilterHandler;
use App\Service\Embedding\FilterInputVectorizer;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Task::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addFilter('query', [FilterOperator::Like])
            ->setInputTransformer(FilterInputVectorizer::class)
            ->setHandler(EmbeddingVectorFilterHandler::class);

        $config->addFilter('projectId', [FilterOperator::Eq])
           ->setProperty('project')
           ->addConstraint(new Assert\Uuid());

        $config->addFilter('status', [FilterOperator::Eq, FilterOperator::In])
           ->addConstraint(new Assert\Choice(choices: array_map(
               static fn (TaskStatus $status) => $status->value,
               TaskStatus::cases(),
           )));

        $config->addSortable('updatedAt');
        $config->addSortable('createdAt');
        $config->addSortable('id');
    }
}
