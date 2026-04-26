<?php

namespace App\Component\Searcher\Definition;

use App\Component\Searcher\Enum\OperatorInterface;
use Symfony\Component\Validator\Constraint;

final class FilterDefinition extends AbstractDefinition
{
    /** @var array<Constraint> */
    private array $constraints = [];

    private \Closure|string|null $inputTransformer = null;

    private \Closure|string|null $handler = null;

    /**
     * @param array<OperatorInterface> $operators
     */
    public function __construct(
        string $name,
        private readonly array $operators,
    ) {
        parent::__construct($name);
    }

    /**
     * @return array<OperatorInterface>
     */
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

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function setProperty(string $property): self
    {
        parent::setProperty($property);

        return $this;
    }

    public function getInputTransformer(): \Closure|string|null
    {
        return $this->inputTransformer;
    }

    public function setInputTransformer(string|\Closure $transformer): self
    {
        $this->inputTransformer = $transformer;

        return $this;
    }

    public function getHandler(): \Closure|string|null
    {
        return $this->handler;
    }

    public function setHandler(string|\Closure $handler): self
    {
        $this->handler = $handler;

        return $this;
    }
}
