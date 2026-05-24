<?php

namespace App\Mcp\Resource;

use App\Exception\EntityNotFoundException;
use App\Factory\Notebook\NotebookResponseDtoFactory;
use App\Service\Notebook\NotebookManager;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Schema\Content\TextResourceContents;
use Symfony\Component\Uid\Uuid;

class NotebookResource extends AbstractResource
{
    public function __construct(
        private readonly NotebookManager $notebookManager,
        private readonly NotebookResponseDtoFactory $factory,
    ) {
    }

    /**
     * Retrieve a notebook by its UUID.
     */
    #[McpResourceTemplate(
        uriTemplate: 'notebook://{notebookId}',
        name: 'notebook',
        description: 'Single notebook by UUID. Contains: id, title, description, createdAt, updatedAt.',
        mimeType: 'application/json',
    )]
    public function get(string $notebookId): TextResourceContents
    {
        try {
            $notebook = $this->notebookManager->get(Uuid::fromString($notebookId));
        } catch (EntityNotFoundException) {
            throw new ResourceNotFoundException(sprintf('notebook://%s', $notebookId));
        }

        $dto = $this->factory->create($notebook);

        return $this->textResource("notebook://{$dto->id}", $dto);
    }
}
