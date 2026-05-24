<?php

namespace App\Component\Searcher\Resolver;

use App\Component\Searcher\Dto\SearchableInterface;
use App\Component\Searcher\Dto\SearchQuery;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\Request\CallToolRequest;

/**
 * Resolver for SearchQuery from MCP tool arguments.
 *
 * Transforms MCP CallToolRequest arguments into a typed SearchQuery with FilterCondition[],
 * SortInstruction[], and PaginationDetails. The SearchDefinition is passed explicitly by the
 * caller (read from the handler's #[MapSearch] attribute), not derived from the DTO type.
 *
 * Argument format:
 * - filter: {field: operator:value, ...}
 * - sort: field1;-field2;field3
 * - limit: number of records
 * - offset: records to skip
 */
final class McpSearchDtoResolver extends AbstractSearchDtoResolver
{
    /**
     * @param class-string $class           DTO class to instantiate (must be SearchQuery or subclass)
     * @param class-string $definitionClass SearchDefinition that configures this search
     *
     * @return SearchableInterface|null Resolved query or null if request type unsupported
     */
    public function resolve(Request $request, string $class, string $definitionClass): ?SearchableInterface
    {
        if (!is_a($class, SearchQuery::class, true)) {
            return null;
        }

        if ($request instanceof CallToolRequest) {
            return $this->create($class, $request->arguments, $definitionClass);
        }

        return null;
    }
}
