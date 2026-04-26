<?php

namespace App\Component\Searcher\Resolver;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\FilterDefinition;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Component\Searcher\Enum\FilterOperator;
use App\Component\Searcher\Enum\OperatorInterface;
use App\Component\Searcher\Enum\SortDirection;
use App\Component\Searcher\Loader\SearchDefinitionLoader;
use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSearchDtoResolver
{
    private const int DEFAULT_LIMIT = 20;
    private const int DEFAULT_OFFSET = 0;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SearchDefinitionLoader $definitionLoader,
        private readonly ContainerInterface $container,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Create a SearchDto instance from HTTP request query parameters.
     *
     * @param class-string<AbstractSearchDto> $class
     * @param array<string, mixed>            $parameters
     *
     * @throws UnprocessableEntityHttpException if validation fails
     */
    protected function create(string $class, array $parameters): AbstractSearchDto
    {
        $filterDefinitions = $this->getFilterDefinitionsForClass($class);
        $filters = $this->parseFilters($parameters, $filterDefinitions);
        $sorting = $this->parseSorting($parameters);
        $pagination = $this->parsePagination($parameters);

        $payload = new \stdClass();
        $constraints = [];

        if (!empty($filters)) {
            $payload->filter = [];
            $filterConstraints = [];
            foreach ($filters as $filter) {
                $payload->filter[$filter->name] = $filter->value;
                if (isset($filterDefinitions[$filter->name])) {
                    $filterConstraints[$filter->name] = $this->buildFilterConstraint($filter->operator, $filterDefinitions[$filter->name]);
                }
            }
            if (!empty($filterConstraints)) {
                $constraints['filter'] = new Assert\Collection(
                    fields: $filterConstraints,
                    allowExtraFields: true
                );
            }
        }

        if (!empty($parameters['limit']) || !empty($parameters['offset'])) {
            $payload->pagination = [];
            $paginationConstraints = [];

            if (!empty($parameters['limit'])) {
                $payload->pagination['limit'] = $parameters['limit'];
                $paginationConstraints['limit'] = new Assert\Regex('/^\d+$/');
            }

            if (!empty($parameters['offset'])) {
                $payload->pagination['offset'] = $parameters['offset'];
                $paginationConstraints['offset'] = new Assert\Regex('/^\d+$/');
            }

            if (!empty($paginationConstraints)) {
                $constraints['pagination'] = new Assert\Collection(
                    fields: $paginationConstraints
                );
            }
        }

        try {
            if (!empty($constraints)) {
                $this->validatePayload($payload, $constraints);
            }
        } finally {
            unset($payload, $constraints);
        }

        return new $class($filters, $sorting, $pagination);
    }

    /**
     * Validate payload sections (filter, pagination, etc.) against their constraints.
     *
     * @param array<string, Constraint> $constraints
     *
     * @throws ValidationFailedException if validation fails
     */
    private function validatePayload(object $payload, array $constraints): void
    {
        $violations = $this->validator->validate($payload, new Assert\Callback(
            function (object $object, ExecutionContextInterface $context) use ($constraints) {
                foreach ($constraints as $section => $constraint) {
                    if (isset($object->$section)) {
                        $context->getValidator()
                            ->inContext($context)
                            ->atPath($section)
                            ->validate($object->$section, $constraint);
                    }
                }
            }
        ));

        if ($violations->count() > 0) {
            throw new ValidationFailedException($payload, $violations);
        }
    }

    /**
     * Build a constraint for a filter based on operator type.
     *
     * @return array<Constraint>|All|Constraint
     */
    private function buildFilterConstraint(OperatorInterface $operator, FilterDefinition $filterDefinition): array|All|Constraint
    {
        $constraints = $filterDefinition->getConstraints();

        if (empty($constraints)) {
            return [];
        }

        if (in_array($operator, [FilterOperator::In, FilterOperator::NotIn], true)) {
            if (1 === count($constraints)) {
                return new All($constraints[0]);
            }

            return array_map(fn ($c) => new All($c), $constraints);
        }

        return $constraints;
    }

    /**
     * Get all filter definitions from SearchDefinition specified in the DTO's Searchable attribute.
     *
     * @param class-string<AbstractSearchDto> $class
     *
     * @return array<string, FilterDefinition>
     */
    private function getFilterDefinitionsForClass(string $class): array
    {
        $definition = $this->definitionLoader->load($class);

        $filterDefinitions = [];
        $configurator = new SearchConfigurator();
        $definition->configure($configurator);

        foreach ($configurator->getFilterDefinitions() as $name => $filterDef) {
            $filterDefinitions[$name] = $filterDef;
        }

        return $filterDefinitions;
    }

    /**
     * Parse filter conditions from query parameters.
     *
     * Supports:
     * - filter[field]=value (eq operator)
     * - filter[field]=eq:value
     * - filter[field]=in:value1;value2
     * - filter[field]=gte:2025-01-01;lte:2025-12-31
     *
     * Validates parsed values against constraints defined in FilterDefinition.
     *
     * @param array<string, mixed>            $parameters
     * @param array<string, FilterDefinition> $filterDefinitions
     *
     * @return array<FilterCondition>
     *
     * @throws ValidationFailedException if any filter value fails validation
     */
    private function parseFilters(array $parameters, array $filterDefinitions): array
    {
        $filters = [];

        if (!isset($parameters['filter'])) {
            foreach ($parameters as $name => $value) {
                if (str_starts_with($name, 'filter[') && str_ends_with($name, ']')) {
                    $key = substr($name, 7, -1);
                    $filters[$key] = $value;
                }
            }
        } else {
            $filterParams = (array) $parameters['filter'];
        }

        if (empty($filterParams)) {
            return [];
        }

        foreach ($filterParams as $field => $filterValue) {
            if (!isset($filterDefinitions[$field])) {
                continue;
            }

            if (!is_string($filterValue) || empty($filterValue)) {
                continue;
            }

            // Split by ; to get multiple conditions for the same field
            $conditions = explode(';', $filterValue);

            foreach ($conditions as $condition) {
                $condition = trim($condition);
                if (empty($condition)) {
                    continue;
                }

                $parts = explode(':', $condition, 2);
                $operator = 'eq';
                $value = $condition;

                if (2 === count($parts)) {
                    $operator = $parts[0];
                    $value = $parts[1];
                }

                try {
                    $operatorEnum = $this->resolveOperator($operator);
                } catch (\ValueError) {
                    $this->logger?->notice(
                        'Unknown filter operator',
                        ['field' => $field, 'operator' => $operator]
                    );
                    continue;
                }

                $parsedValue = $this->parseFilterValue($operatorEnum, $value);

                if ($filterDefinitions[$field]->getInputTransformer()) {
                    $transformer = $this->resolveService($filterDefinitions[$field]->getInputTransformer());
                    $parsedValue = $transformer($operatorEnum, $parsedValue);
                }

                $filters[] = new FilterCondition($field, $operatorEnum, $parsedValue);
            }
        }

        return $filters;
    }

    /**
     * Resolve an operator string to FilterOperator enum.
     *
     * @throws \ValueError if operator is unknown
     */
    private function resolveOperator(string $operator): FilterOperator
    {
        return FilterOperator::from($operator);
    }

    /**
     * Parse filter value based on operator type.
     * - in/not_in operators produce arrays (comma-separated)
     * - other operators produce scalars.
     *
     * @return string|array<string>
     */
    private function parseFilterValue(FilterOperator $operator, string $value): string|array
    {
        return match ($operator) {
            FilterOperator::In, FilterOperator::NotIn => array_filter(
                array_map('trim', explode(',', $value))
            ),
            default => $value,
        };
    }

    /**
     * Parse sorting from query parameters.
     *
     * Supports:
     * - sort=field (ASC)
     * - sort=-field (DESC)
     * - sort=field1;field2;-field3 (multiple fields)
     *
     * @param array<string, mixed> $parameters
     *
     * @return array<SortInstruction>
     */
    private function parseSorting(array $parameters): array
    {
        $sorting = [];
        $sortParam = $parameters['sort'] ?? '';

        if (!is_string($sortParam) || empty($sortParam)) {
            return [];
        }

        $fields = explode(';', $sortParam);

        foreach ($fields as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            if (str_starts_with($field, '-')) {
                $fieldName = substr($field, 1);
                $direction = SortDirection::DESC;
            } else {
                $fieldName = $field;
                $direction = SortDirection::ASC;
            }

            if (empty($fieldName)) {
                continue;
            }

            $sorting[] = new SortInstruction($fieldName, $direction);
        }

        return $sorting;
    }

    /**
     * Parse pagination parameters from query.
     *
     * Parameters:
     * - limit: number of records (default: 20)
     * - offset: records to skip (default: 0)
     *
     * @param array<string, mixed> $parameters
     */
    private function parsePagination(array $parameters): PaginationDetails
    {
        $getDigits = static fn (string $key, int $default) => preg_replace(
            '/[^[:digit:]]/',
            '',
            (string) ($parameters[$key] ?? $default),
        );
        $limit = $getDigits('limit', self::DEFAULT_LIMIT);
        $offset = $getDigits('offset', self::DEFAULT_OFFSET);

        if ($limit < 0) {
            $limit = self::DEFAULT_LIMIT;
        }

        if ($offset < 0) {
            $offset = self::DEFAULT_OFFSET;
        }

        return new PaginationDetails($limit, $offset);
    }

    /**
     * Resolve a service from container (either class string or callable).
     */
    private function resolveService(string|callable $service): callable
    {
        if (is_callable($service) && !is_string($service)) {
            return $service;
        }

        return $this->container->get($service);
    }
}
