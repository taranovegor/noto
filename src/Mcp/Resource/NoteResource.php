<?php

namespace App\Mcp\Resource;

use App\Exception\EntityNotFoundException;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Note\NoteManager;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Schema\Content\TextResourceContents;
use Symfony\Component\Uid\Uuid;

class NoteResource extends AbstractResource
{
    public function __construct(
        private readonly NoteManager $noteManager,
        private readonly NoteResponseDtoFactory $factory,
    ) {
    }

    /**
     * Retrieve a note by its UUID.
     */
    #[McpResourceTemplate(
        uriTemplate: 'note://{noteId}',
        name: 'note',
        description: 'Single note by UUID. Contains: id, notebookId, title, content, createdAt, updatedAt, attachments.',
        mimeType: 'application/json',
    )]
    public function get(string $noteId): TextResourceContents
    {
        try {
            $note = $this->noteManager->get(Uuid::fromString($noteId));
        } catch (EntityNotFoundException) {
            throw new ResourceNotFoundException(sprintf('note://%s', $noteId));
        }

        $dto = $this->factory->create($note);

        return $this->textResource("note://{$dto->id}", $dto);
    }
}
