<?php

namespace App\Component\Searcher\Resolver;

use App\Component\Searcher\Dto\AbstractSearchDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Symfony ValueResolver for automatic SearchDto resolution from HTTP requests.
 *
 * Transforms query parameters into typed DTO instances with FilterCondition[], SortInstruction[],
 * and PaginationDetails objects. Gracefully handles invalid input by logging and providing defaults.
 *
 * Query parameters:
 * - filter[field]=operator:value
 * - sort=-field (- prefix = DESC)
 * - limit=20&offset=0
 *
 * Validates filter values against constraints defined in SearchDefinition.
 */
final class SearchDtoValueResolver extends AbstractSearchDtoResolver implements ValueResolverInterface
{
    /** @return iterable<AbstractSearchDto> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getType() || !is_a($argument->getType(), AbstractSearchDto::class, true)) {
            return [];
        }

        $class = $argument->getType();

        try {
            yield $this->create($class, $request->query->all());
        } catch (ValidationFailedException $e) {
            throw new UnprocessableEntityHttpException(previous: $e);
        }
    }
}
