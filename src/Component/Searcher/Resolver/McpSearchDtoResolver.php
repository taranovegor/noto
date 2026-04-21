<?php

namespace App\Component\Searcher\Resolver;

use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Component\Searcher\Dto\FilterableInterface;
use App\Component\Searcher\Dto\PaginableInterface;
use App\Component\Searcher\Dto\SortableInterface;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\Request\CallToolRequest;

/**
 * Resolver for SearchDto from MCP tool arguments.
 *
 * Transforms MCP CallToolRequest arguments into typed DTO instances with FilterCondition[],
 * SortInstruction[], and PaginationDetails objects. Validates filter values against
 * constraints defined in SearchDefinition.
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
     * Resolve SearchDto from MCP tool arguments.
     *
     * @param class-string $class DTO class to instantiate
     *
     * @return FilterableInterface|SortableInterface|PaginableInterface|null Resolved DTO or null if request type unsupported
     */
    public function resolve(Request $request, string $class): FilterableInterface|SortableInterface|PaginableInterface|null
    {
        if (!is_a($class, AbstractSearchDto::class, true)) {
            return null;
        }

        if ($request instanceof CallToolRequest) {
            return $this->create($class, $request->arguments);
        }

        return null;
    }
}
