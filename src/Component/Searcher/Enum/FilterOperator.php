<?php

namespace App\Component\Searcher\Enum;

enum FilterOperator: string implements OperatorInterface
{
    case Eq = 'eq';
    case Neq = 'neq';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case In = 'in';
    case NotIn = 'notIn';
    case Like = 'like';

    public function getName(): string
    {
        return $this->value;
    }
}
