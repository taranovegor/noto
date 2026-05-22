<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Dto\Memo\CreateMemoDto;
use App\Dto\Memo\SearchMemoDto;
use App\Dto\Memo\UpdateMemoDto;
use App\Entity\Memo;
use App\Factory\Memo\MemoResponseDtoFactory;
use App\Service\Memo\MemoManager;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

class MemoTool extends AbstractTool
{
    /**
     * @param SearcherInterface<Memo> $searcher
     */
    public function __construct(
        private readonly MemoManager $memoManager,
        private readonly MemoResponseDtoFactory $factory,
        private readonly SearcherInterface $searcher,
    ) {
    }

    #[McpTool(
        name: 'search_memos',
        description: '[Query Tool] Search and filter memos with optional sorting and pagination.',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'filter' => [
            'type' => ['object', 'null'],
            'description' => 'Filter conditions object with field-value pairs.',
            'properties' => [
                'query' => [
                    'type' => ['string', 'null'],
                    'description' => 'Search query text (full-text search)',
                    'example' => 'I left my keys at',
                ],
            ],
        ],
        'sort' => [
            'type' => ['string', 'null'],
            'description' => 'Sort fields separated by semicolon. Prefix with - for DESC, omit or use no prefix for ASC. Examples: "-updatedAt" (newest first), "createdAt" (oldest first)',
            'example' => '-updatedAt',
            'default' => '',
        ],
        'limit' => [
            'type' => ['integer', 'null'],
            'description' => 'Maximum number of results to return (pagination page size)',
            'example' => 20,
            'default' => 20,
            'minimum' => 1,
        ],
        'offset' => [
            'type' => ['integer', 'null'],
            'description' => 'Number of results to skip, for pagination. Use with limit to paginate through results.',
            'example' => 0,
            'default' => 0,
            'minimum' => 0,
        ],
    ])]
    public function search(
        RequestContext $context,
    ): CallToolResult {
        return $this->handle($context, function (SearchMemoDto $dto): CallToolResult {
            return $this->success(
                $this->searcher->search($dto)->map($this->factory->create(...))->getData(),
                [AbstractNormalizer::GROUPS => ['memo:list']],
            );
        });
    }

    #[McpTool(
        name: 'create_memo',
        description: '[Create Tool] Create a new memo.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'content' => [
            'type' => 'string',
            'description' => 'Memo content/body. First line starting with # is used as the title.',
            'minLength' => 1,
            'example' => '# My Memo\n\nThis is the content...',
        ],
    ], required: ['content'])]
    public function create(RequestContext $context): CallToolResult
    {
        return $this->handle($context, function (CreateMemoDto $dto): object {
            $memo = $this->memoManager->create($dto);

            return $this->success($this->factory->create($memo), [
                AbstractNormalizer::GROUPS => ['memo:read'],
            ]);
        });
    }

    #[McpTool(
        name: 'update_memo',
        description: '[Update Tool] Update one or more fields of an existing memo.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'content' => [
            'type' => ['string', 'null'],
            'description' => 'Update memo content/body. First line starting with # is used as the title. Omit to keep the current value.',
            'minLength' => 1,
            'example' => '# Updated Title\n\nUpdated content...',
        ],
    ])]
    public function update(
        RequestContext $context,
        #[Schema(description: 'UUID of the memo to update', format: 'uuid')]
        string $memoId,
    ): CallToolResult {
        return $this->handle($context, function (UpdateMemoDto $dto) use ($memoId): object {
            $memo = $this->memoManager->get(Uuid::fromString($memoId));
            $this->memoManager->update($memo, $dto);

            return $this->success($this->factory->create($memo), [
                AbstractNormalizer::GROUPS => ['memo:read'],
            ]);
        });
    }
}
