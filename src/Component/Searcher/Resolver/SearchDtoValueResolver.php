<?php

namespace App\Component\Searcher\Resolver;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchableInterface;
use App\Component\Searcher\Dto\SearchQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Resolves a SearchQuery argument annotated with #[MapSearch] from the request query string.
 *
 * Transforms query parameters into a typed SearchQuery with FilterCondition[], SortInstruction[],
 * and PaginationDetails. The SearchDefinition comes from the attribute, not the argument type.
 *
 * Query parameters:
 * - filter[field]=operator:value
 * - sort=-field (- prefix = DESC)
 * - limit=20&offset=0
 */
final class SearchDtoValueResolver extends AbstractSearchDtoResolver implements ValueResolverInterface
{
    /** @return iterable<SearchQuery> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $mapSearch = $argument->getAttributesOfType(MapSearch::class)[0] ?? null;
        if (!$mapSearch instanceof MapSearch) {
            return [];
        }

        $type = $argument->getType();
        if (null === $type || !is_a($type, SearchableInterface::class, true)) {
            return [];
        }

        try {
            yield $this->create(SearchQuery::class, $request->query->all(), $mapSearch->definition);
        } catch (ValidationFailedException $e) {
            throw new UnprocessableEntityHttpException(previous: $e);
        }
    }
}
