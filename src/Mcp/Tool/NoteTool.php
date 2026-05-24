<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Note;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Note\NoteFinder;
use App\Service\Note\NoteManager;
use App\Service\Note\NoteSearchDefinition;
use App\Service\Notebook\NotebookManager;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

class NoteTool extends AbstractTool
{
    public function __construct(
        private readonly NoteManager $noteManager,
        /** @var NoteFinder<Note> */
        private readonly NoteFinder $noteFinder,
        private readonly NotebookManager $notebookManager,
        private readonly NoteResponseDtoFactory $factory,
    ) {
    }

    #[McpTool(
        name: 'search_notes',
        description: '[Query Tool] Search and filter notes within a notebook with optional sorting and pagination.',
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
                    'description' => 'Search query text (full-text search across title and content)',
                    'example' => 'meeting notes',
                ],
            ],
        ],
        'sort' => [
            'type' => ['string', 'null'],
            'description' => 'Sort fields separated by semicolon. Prefix with - for DESC. Available: title, createdAt, updatedAt.',
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
    public function search(
        RequestContext $context,
        #[Schema(description: 'UUID of the notebook to search notes in', format: 'uuid')]
        string $notebookId,
    ): CallToolResult {
        return $this->handle($context, function (#[MapSearch(NoteSearchDefinition::class)] SearchQuery $query) use ($notebookId): CallToolResult {
            $notebook = $this->notebookManager->get(Uuid::fromString($notebookId));

            return $this->success(
                $this->noteFinder->inNotebook($notebook, $query)->map($this->factory->create(...))->getData(),
                [AbstractNormalizer::GROUPS => ['note:list', 'attachment:read']],
            );
        });
    }

    #[McpTool(
        name: 'create_note',
        description: '[Create Tool] Create a new note in a notebook.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'title' => [
            'type' => 'string',
            'description' => 'Note title',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Meeting Summary',
        ],
        'content' => [
            'type' => 'string',
            'description' => 'Note content/body',
            'minLength' => 1,
            'example' => 'Discussed Q3 roadmap and resource allocation.',
        ],
    ], required: ['title', 'content'])]
    public function create(
        RequestContext $context,
        #[Schema(description: 'UUID of the notebook to create the note in', format: 'uuid')]
        string $notebookId,
    ): CallToolResult {
        return $this->handle($context, function (CreateNoteDto $dto) use ($notebookId): CallToolResult {
            $notebook = $this->notebookManager->get(Uuid::fromString($notebookId));
            $note = $this->noteManager->create($notebook, $dto);

            return $this->success($this->factory->create($note), [
                AbstractNormalizer::GROUPS => ['note:read', 'attachment:read'],
            ]);
        });
    }

    #[McpTool(
        name: 'update_note',
        description: '[Update Tool] Update one or more fields of an existing note.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'title' => [
            'type' => ['string', 'null'],
            'description' => 'New note title. Omit to keep current value.',
            'maxLength' => 255,
        ],
        'content' => [
            'type' => ['string', 'null'],
            'description' => 'New note content. Omit to keep current value.',
        ],
    ])]
    public function update(
        RequestContext $context,
        #[Schema(description: 'UUID of the note to update', format: 'uuid')]
        string $noteId,
    ): CallToolResult {
        return $this->handle($context, function (UpdateNoteDto $dto) use ($noteId): CallToolResult {
            $note = $this->noteManager->get(Uuid::fromString($noteId));
            $this->noteManager->update($note, $dto);

            return $this->success($this->factory->create($note), [
                AbstractNormalizer::GROUPS => ['note:read', 'attachment:read'],
            ]);
        });
    }
}
