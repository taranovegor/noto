<?php

namespace App\Component\Searcher\OpenApi;

use App\Component\OpenApi\RouteDescriber\RouteDescriberTrait;
use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\OpenApi;
use Symfony\Component\Routing\Route;

final class SearchCriteriaDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(
        OpenApi $api,
        Route $route,
        \ReflectionMethod $reflectionMethod,
    ): void {
        $definition = $this->getSearchDefinition($reflectionMethod);
        if (null === $definition) {
            return;
        }

        $configurator = new SearchConfigurator();
        $definition->configure($configurator);

        foreach ($this->getOperations($api, $route) as $operation) {
            if ($configurator->isPaginable()) {
                $this->addPaginationParameters($operation, $configurator);
            }
            $this->addSortParameters($operation, $configurator);
            $this->addFilterParameters($operation, $configurator);
        }
    }

    private function getSearchDefinition(\ReflectionMethod $method): ?SearchableDefinitionInterface
    {
        foreach ($method->getParameters() as $param) {
            $attrs = $param->getAttributes(MapSearch::class);
            if (empty($attrs)) {
                continue;
            }

            /** @var MapSearch $attr */
            $attr = $attrs[0]->newInstance();
            $definitionClass = $attr->definition;
            $definition = new $definitionClass();

            if ($definition instanceof SearchableDefinitionInterface) {
                return $definition;
            }
        }

        return null;
    }

    private function addPaginationParameters(OA\Operation $operation, SearchConfigurator $configurator): void
    {
        $limit = Util::getOperationParameter($operation, 'limit', 'query');
        $limit->description = sprintf('Number of records to return (max: %d)', $configurator->getMaxLimit() ?: 999);
        $limit->required = false;
        /** @var OA\Schema $schema */
        $schema = Util::getChild($limit, OA\Schema::class);
        $schema->type = 'integer';
        $schema->default = $configurator->getDefaultLimit();

        $offset = Util::getOperationParameter($operation, 'offset', 'query');
        $offset->description = 'Number of records to skip';
        $offset->required = false;
        /** @var OA\Schema $schema */
        $schema = Util::getChild($offset, OA\Schema::class);
        $schema->type = 'integer';
        $schema->default = 0;
    }

    private function addSortParameters(OA\Operation $operation, SearchConfigurator $configurator): void
    {
        $sortFields = array_keys($configurator->getSortDefinitions());
        if (empty($sortFields)) {
            return;
        }

        $sort = Util::getOperationParameter($operation, 'sort', 'query');
        $sort->description = sprintf(
            'Sort by field(s). Prefix with - for descending. Separate multiple with ;. Available: %s',
            implode(', ', $sortFields),
        );
        $sort->required = false;
        /** @var OA\Schema $schema */
        $schema = Util::getChild($sort, OA\Schema::class);
        $schema->type = 'string';
    }

    private function addFilterParameters(OA\Operation $operation, SearchConfigurator $configurator): void
    {
        $filterDefinitions = $configurator->getFilterDefinitions();

        if (empty($filterDefinitions)) {
            return;
        }

        foreach ($filterDefinitions as $fieldName => $filterDef) {
            $paramName = sprintf('filter[%s]', $fieldName);
            $param = Util::getOperationParameter($operation, $paramName, 'query');

            $operators = implode(', ', array_map(static fn ($op) => $op->getName(), $filterDef->getOperators()));
            $param->description = sprintf('Filter by %s. Operators: %s', $fieldName, $operators);
            $param->required = false;

            /** @var OA\Schema $schema */
            $schema = Util::getChild($param, OA\Schema::class);
            $schema->type = 'string';
        }
    }
}
