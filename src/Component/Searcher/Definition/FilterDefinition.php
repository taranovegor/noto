<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Enum\OperatorInterface;
use Symfony\Component\Validator\Constraint;

final class FilterDefinition extends AbstractDefinition
{
    /** @var Constraint[] */
    private array $constraints = [];

    /**
     * @param OperatorInterface[] $operators
     */
    public function __construct(
        string $name,
        private readonly array $operators,
    ) {
        parent::__construct($name);
    }

    /** @return OperatorInterface[] */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * Add a Symfony validator constraint.
     */
    public function addConstraint(Constraint $constraint): self
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    /** @return Constraint[] */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
