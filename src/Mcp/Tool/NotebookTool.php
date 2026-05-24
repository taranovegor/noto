<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Notebook\CreateNotebookDto;
use App\Dto\Notebook\UpdateNotebookDto;
use App\Entity\Notebook;
use App\Factory\Notebook\NotebookResponseDtoFactory;
use App\Service\Notebook\NotebookManager;
use App\Service\Notebook\NotebookSearchDefinition;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

class NotebookTool extends AbstractTool
{
    /**
     * @param SearcherInterface<Notebook> $searcher
     */
    public function __construct(
        private readonly NotebookManager $notebookManager,
        private readonly NotebookResponseDtoFactory $factory,
        private readonly SearcherInterface $searcher,
    ) {
    }

    #[McpTool(
        name: 'search_notebooks',
        description: '[Query Tool] Search and filter notebooks with optional sorting and pagination.',
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
                    'description' => 'Search query text (full-text search across title and description)',
                    'example' => 'project planning',
                ],
            ],
        ],
        'sort' => [
            'type' => ['string', 'null'],
            'description' => 'Sort fields separated by semicolon. Prefix with - for DESC. Available: createdAt, updatedAt.',
            'example' => '-updatedAt',
        ],
        'limit' => [
            'type' => ['integer', 'null'],
            'description' => 'Maximum number of results to return',
            'example' => 20,
            'default' => 20,
            'minimum' => 1,
        ],
        'offset' => [
            'type' => ['integer', 'null'],
            'description' => 'Number of results to skip for pagination',
            'example' => 0,
            'default' => 0,
            'minimum' => 0,
        ],
    ])]
    public function search(RequestContext $context): CallToolResult
    {
        return $this->handle($context, function (#[MapSearch(NotebookSearchDefinition::class)] SearchQuery $query): CallToolResult {
            return $this->success(
                $this->searcher->search($query)->map($this->factory->create(...))->getData(),
                [AbstractNormalizer::GROUPS => ['notebook:list']],
            );
        });
    }

    #[McpTool(
        name: 'create_notebook',
        description: '[Create Tool] Create a new notebook.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'title' => [
            'type' => 'string',
            'description' => 'Notebook title',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Project Alpha',
        ],
        'description' => [
            'type' => 'string',
            'description' => 'Notebook description',
            'minLength' => 1,
            'example' => 'All notes related to Project Alpha',
        ],
    ], required: ['title', 'description'])]
    public function create(RequestContext $context): CallToolResult
    {
        return $this->handle($context, function (CreateNotebookDto $dto): CallToolResult {
            $notebook = $this->notebookManager->create($dto);

            return $this->success($this->factory->create($notebook), [
                AbstractNormalizer::GROUPS => ['notebook:read'],
            ]);
        });
    }

    #[McpTool(
        name: 'update_notebook',
        description: '[Update Tool] Update one or more fields of an existing notebook.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'title' => [
            'type' => ['string', 'null'],
            'description' => 'New notebook title. Omit to keep current value.',
            'maxLength' => 255,
        ],
        'description' => [
            'type' => ['string', 'null'],
            'description' => 'New notebook description. Omit to keep current value.',
        ],
    ])]
    public function update(
        RequestContext $context,
        #[Schema(description: 'UUID of the notebook to update', format: 'uuid')]
        string $notebookId,
    ): CallToolResult {
        return $this->handle($context, function (UpdateNotebookDto $dto) use ($notebookId): CallToolResult {
            $notebook = $this->notebookManager->get(Uuid::fromString($notebookId));
            $this->notebookManager->update($notebook, $dto);

            return $this->success($this->factory->create($notebook), [
                AbstractNormalizer::GROUPS => ['notebook:read'],
            ]);
        });
    }
}
