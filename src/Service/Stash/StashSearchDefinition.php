<?php

namespace App\Service\Stash;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Context\DoctrineFilterContext;
use App\Component\Searcher\Definition\SearchableDefinitionInterface;
use App\Component\Searcher\Enum\FilterOperator;
use App\Entity\Stash;

final class StashSearchDefinition implements SearchableDefinitionInterface
{
    public function getEntityClass(): string
    {
        return Stash::class;
    }

    public function configure(SearchConfigurator $config): void
    {
        $config->addFilter('active', [FilterOperator::Eq])
            ->setHandler(static function (DoctrineFilterContext $context, FilterOperator $operator, bool $value): void {
                $expr = $context->expr();
                $rootAlias = $context->getRootAlias();

                if ($value) {
                    $context->andWhere(
                        $expr->orX(
                            $expr->eq("$rootAlias.pinned", ':pinned'),
                            $expr->isNull("$rootAlias.expiresAt"),
                            $expr->gte("$rootAlias.expiresAt", ':now')
                        )
                    );
                } else {
                    $context->andWhere(
                        $expr->neq("$rootAlias.pinned", ':pinned'),
                        $expr->lt("$rootAlias.expiresAt", ':now')
                    );
                }
                $context->setParameter('pinned', true);
                $context->setParameter('now', new \DateTime());
            });

        $config->addSortable('createdAt');
        $config->addSortable('updatedAt');
        $config->addSortable('pinned');
    }
}
