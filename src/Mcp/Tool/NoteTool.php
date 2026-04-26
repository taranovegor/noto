<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\SearchNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Note;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Note\NoteManager;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

class NoteTool extends AbstractTool
{
    /**
     * @param SearcherInterface<Note> $searcher
     */
    public function __construct(
        private readonly NoteManager $noteManager,
        private readonly NoteResponseDtoFactory $factory,
        private readonly SearcherInterface $searcher,
    ) {
    }

    #[McpTool(
        name: 'search_notes',
        description: '[Query Tool] Search and filter notes with optional sorting and pagination.',
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
        return $this->handle($context, function (SearchNoteDto $dto): CallToolResult {
            return $this->success(
                $this->searcher->search($dto)->map($this->factory->create(...))->getData(),
                [AbstractNormalizer::GROUPS => ['note:list']],
            );
        });
    }

    #[McpTool(
        name: 'create_note',
        description: '[Create Tool] Create a new note.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'title' => [
            'type' => 'string',
            'description' => 'Note title (1-255 characters)',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Project Notes',
        ],
        'content' => [
            'type' => 'string',
            'description' => 'Note content/body',
            'minLength' => 1,
            'example' => 'This is the content of the note...',
        ],
    ], required: ['title', 'content'])]
    public function create(RequestContext $context): CallToolResult
    {
        return $this->handle($context, function (CreateNoteDto $dto): object {
            $note = $this->noteManager->create($dto);

            return $this->success($this->factory->create($note), [
                AbstractNormalizer::GROUPS => ['note:read'],
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
            'description' => 'New note title (1-255 characters). Omit to keep the current value.',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Updated Title',
        ],
        'content' => [
            'type' => ['string', 'null'],
            'description' => 'Update note content/body. Omit to keep the current value.',
            'minLength' => 1,
            'example' => 'Updated content...',
        ],
    ])]
    public function update(
        RequestContext $context,
        #[Schema(description: 'UUID of the note to update', format: 'uuid')]
        string $noteId,
    ): CallToolResult {
        return $this->handle($context, function (UpdateNoteDto $dto) use ($noteId): object {
            $note = $this->noteManager->get(Uuid::fromString($noteId));
            $this->noteManager->update($note, $dto);

            return $this->success($this->factory->create($note), [
                AbstractNormalizer::GROUPS => ['note:read'],
            ]);
        });
    }
}
