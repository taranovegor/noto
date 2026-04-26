<?php

namespace App\Component\Searcher\Context;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;

readonly class DoctrineFilterContext implements FilterContextInterface
{
    public function __construct(
        private string $rootAlias,
        private DoctrineQueryBuilder $qb,
    ) {
    }

    public function getRootAlias(): string
    {
        return $this->rootAlias;
    }

    public function expr(): Expr
    {
        return $this->qb->expr();
    }

    public function andWhere(mixed ...$where): self
    {
        $this->qb->andWhere(...$where);

        return $this;
    }

    public function setParameter(string $key, mixed $value, ?string $type = null): self
    {
        $this->qb->setParameter($key, $value, $type);

        return $this;
    }

    public function addOrderBy(string $sort, ?string $order = null): self
    {
        $this->qb->addOrderBy($sort, $order);

        return $this;
    }

    public function join(string $join, string $alias, ?string $conditionType = null, ?string $condition = null): self
    {
        $this->qb->join($join, $alias, $conditionType, $condition);

        return $this;
    }
}
