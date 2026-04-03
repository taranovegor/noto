<?php

namespace App\Component\Searcher\Resolver;

use App\Component\Searcher\Configurator\SearchConfigurator;
use App\Component\Searcher\Definition\FilterDefinition;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Component\Searcher\Enum\FilterOperator;
use App\Component\Searcher\Enum\SortDirection;
use App\Component\Searcher\Loader\SearchDefinitionLoader;
use App\Component\Searcher\Model\FilterCondition;
use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\Model\SortInstruction;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Symfony ValueResolver for automatic SearchDto resolution from HTTP requests.
 *
 * Transforms query parameters into typed DTO instances with FilterCondition[], SortInstruction[],
 * and PaginationDetails objects. Gracefully handles invalid input by logging and providing defaults.
 *
 * Query parameters follow the CLAUDE.md spec:
 * - filter[field]=operator:value
 * - sort=-field (- prefix = DESC)
 * - limit=20&offset=0
 *
 * Validates filter values against constraints defined in SearchDefinition.
 */
final class SearchDtoValueResolver implements ValueResolverInterface
{
    private const int DEFAULT_LIMIT = 20;
    private const int DEFAULT_OFFSET = 0;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SearchDefinitionLoader $definitionLoader,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /** @return iterable<AbstractSearchDto> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getType() || !is_a($argument->getType(), AbstractSearchDto::class, true)) {
            return [];
        }

        $class = $argument->getType();

        yield $this->createDto($class, $request);
    }

    /**
     * Create a SearchDto instance from HTTP request query parameters.
     *
     * @param class-string<AbstractSearchDto> $class
     *
     * @throws UnprocessableEntityHttpException if validation fails
     */
    private function createDto(string $class, Request $request): AbstractSearchDto
    {
        $filterDefinitions = $this->getFilterDefinitionsForClass($class);
        $filters = $this->parseFilters($request, $filterDefinitions);
        $sorting = $this->parseSorting($request);
        $pagination = $this->parsePagination($request);

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

        if ($request->query->has('limit') || $request->query->has('offset')) {
            $payload->pagination = [];
            $paginationConstraints = [];

            if ($request->query->has('limit')) {
                $payload->pagination['limit'] = $request->query->get('limit');
                $paginationConstraints['limit'] = new Assert\Regex('/^\d+$/');
            }

            if ($request->query->has('offset')) {
                $payload->pagination['offset'] = $request->query->get('offset');
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
     * @throws UnprocessableEntityHttpException if validation fails
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
            throw new UnprocessableEntityHttpException(previous: new ValidationFailedException('query', $violations));
        }
    }

    /**
     * Build a constraint for a filter based on operator type.
     */
    private function buildFilterConstraint(FilterOperator $operator, object $filterDefinition): array|All|Constraint
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
     * @param array<string, FilterDefinition> $filterDefinitions
     *
     * @return FilterCondition[]
     *
     * @throws ValidationFailedException if any filter value fails validation
     */
    private function parseFilters(Request $request, array $filterDefinitions): array
    {
        $filters = [];
        $queryParams = $request->query->all();

        if (!isset($queryParams['filter'])) {
            return [];
        }

        $filterParams = (array) $queryParams['filter'];

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
        return match ($operator) {
            'eq' => FilterOperator::Eq,
            'neq' => FilterOperator::Neq,
            'gt' => FilterOperator::Gt,
            'gte' => FilterOperator::Gte,
            'lt' => FilterOperator::Lt,
            'lte' => FilterOperator::Lte,
            'in' => FilterOperator::In,
            'not_in' => FilterOperator::NotIn,
            default => throw new \ValueError("Unknown operator: $operator"),
        };
    }

    /**
     * Parse filter value based on operator type.
     * - in/not_in operators produce arrays (comma-separated)
     * - other operators produce scalars.
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
     * @return SortInstruction[]
     */
    private function parseSorting(Request $request): array
    {
        $sorting = [];
        $sortParam = $request->query->get('sort');

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
     */
    private function parsePagination(Request $request): PaginationDetails
    {
        $limit = $request->query->getDigits('limit', self::DEFAULT_LIMIT);
        $offset = $request->query->getDigits('offset', self::DEFAULT_OFFSET);

        if ($limit < 0) {
            $limit = self::DEFAULT_LIMIT;
        }

        if ($offset < 0) {
            $offset = self::DEFAULT_OFFSET;
        }

        return new PaginationDetails($limit, $offset);
    }
}
