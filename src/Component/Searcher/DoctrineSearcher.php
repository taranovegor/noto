<?php

namespace App\Component\Searcher;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Component\Searcher\Dto\FilterableInterface;
use App\Component\Searcher\Dto\PaginableInterface;
use App\Component\Searcher\Dto\SearchableInterface;
use App\Component\Searcher\Dto\SortableInterface;
use App\Component\Searcher\Enum\FilterOperator;
use App\Component\Searcher\Loader\SearchDefinitionLoader;
use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\Model\SearchCriteria;
use App\Component\Searcher\Model\SearchResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Doctrine ORM implementation of the search interface.
 *
 * Parses SearchableInterface DTOs, loads their SearchDefinition from the container,
 * and builds/executes DQL queries based on SearchCriteria.
 *
 * @template T
 *
 * @implements SearcherInterface<T>
 */
final readonly class DoctrineSearcher implements SearcherInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SearchDefinitionLoader $searchDefinitionLoader,
    ) {
    }

    /**
     * @return SearchResult<T>
     */
    public function search(FilterableInterface|SortableInterface|PaginableInterface $searchable): SearchResult
    {
        $definition = $this->searchDefinitionLoader->load($searchable::class);
        $configurator = new SearchConfigurator();
        $definition->configure($configurator);

        $qb = $this->entityManager
            ->getRepository($definition->getEntityClass())
            ->createQueryBuilder('e');

        $criteria = $this->buildCriteria($searchable, $definition, $configurator);

        if ($searchable instanceof FilterableInterface && $criteria->hasFilters()) {
            $this->applyFilters($qb, $criteria);
        }

        if ($searchable instanceof SortableInterface && $criteria->hasSorting()) {
            $this->applySorting($qb, $criteria);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(e)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $hasPagination = $searchable instanceof PaginableInterface && $criteria->hasPagination();
        if ($hasPagination) {
            $qb->setMaxResults($criteria->getLimit());
            $qb->setFirstResult($criteria->getOffset());
        }

        $data = $qb->getQuery()->getResult();

        return new SearchResult(
            $data,
            $hasPagination ? new Pagination($criteria->getLimit(), $criteria->getOffset(), $total) : null,
        );
    }

    /**
     * Build SearchCriteria from DTO using SearchDefinition configuration.
     * Only includes fields explicitly allowed by the definition.
     */
    private function buildCriteria(SearchableInterface $searchable, SearchableDefinitionInterface $definition, SearchConfigurator $configurator): SearchCriteria
    {
        $filters = [];
        $sorting = [];
        $limit = null;
        $offset = 0;

        if ($searchable instanceof FilterableInterface) {
            foreach ($searchable->getFilters() as $filterCondition) {
                $name = $filterCondition->name;

                if (!$configurator->isFilterAllowed($name)) {
                    continue;
                }

                $propertyName = $configurator->getFilterDefinition($name)?->getProperty() ?? $name;
                $filters[$propertyName] = $filterCondition;
            }
        }

        if ($searchable instanceof SortableInterface) {
            foreach ($searchable->getSorting() as $sortInstruction) {
                $name = $sortInstruction->name;

                if (!$configurator->isSortAllowed($name)) {
                    continue;
                }

                $propertyName = $configurator->getSortDefinition($name)?->getProperty() ?? $name;
                $sorting[$propertyName] = $sortInstruction->direction;
            }
        }

        if ($searchable instanceof PaginableInterface) {
            $paginationDetails = $searchable->getPagination();
            $limit = $paginationDetails->limit;
            $offset = $paginationDetails->offset;

            if ($configurator->getMaxLimit() > 0 && $limit > $configurator->getMaxLimit()) {
                $limit = $configurator->getMaxLimit();
            }

            if (0 === $limit) {
                $limit = null;
            }
        }

        return new SearchCriteria(
            $definition->getEntityClass(),
            $filters,
            $sorting,
            $limit,
            $offset,
        );
    }

    /**
     * Apply filter conditions to the query builder.
     */
    private function applyFilters(QueryBuilder $qb, SearchCriteria $criteria): void
    {
        $parameterIndex = 0;

        foreach ($criteria->getFilters() as $field => $filterCondition) {
            $paramName = 'filter_'.(++$parameterIndex);

            $expr = $qb->expr();
            match ($filterCondition->operator) {
                FilterOperator::Eq => $qb->andWhere($expr->eq("e.$field", ":$paramName"))->setParameter($paramName, $filterCondition->value),
                FilterOperator::Neq => $qb->andWhere($expr->neq("e.$field", ":$paramName"))->setParameter($paramName, $filterCondition->value),
                FilterOperator::Gt => $qb->andWhere($expr->gt("e.$field", ":$paramName"))->setParameter($paramName, $filterCondition->value),
                FilterOperator::Gte => $qb->andWhere($expr->gte("e.$field", ":$paramName"))->setParameter($paramName, $filterCondition->value),
                FilterOperator::Lt => $qb->andWhere($expr->lt("e.$field", ":$paramName"))->setParameter($paramName, $filterCondition->value),
                FilterOperator::Lte => $qb->andWhere($expr->lte("e.$field", ":$paramName"))->setParameter($paramName, $filterCondition->value),
                FilterOperator::In => $qb->andWhere($expr->in("e.$field", ":$paramName"))->setParameter($paramName, (array) $filterCondition->value),
                FilterOperator::NotIn => $qb->andWhere($expr->notIn("e.$field", ":$paramName"))->setParameter($paramName, (array) $filterCondition->value),
                default => throw new \LogicException(sprintf('Unsupported filter operator: %s', $filterCondition->operator->name)),
            };
        }
    }

    /**
     * Apply sorting to the query builder.
     */
    private function applySorting(QueryBuilder $qb, SearchCriteria $criteria): void
    {
        foreach ($criteria->getSorting() as $field => $direction) {
            $qb->addOrderBy("e.$field", strtoupper($direction->value));
        }
    }
}
