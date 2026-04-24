<?php

namespace App\Mcp\Tool;

use App\Dto\Project\CreateProjectDto;
use App\Dto\Project\UpdateProjectDto;
use App\Factory\Project\ProjectResponseDtoFactory;
use App\Service\Project\ProjectManager;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Uid\Uuid;

class ProjectTool extends AbstractTool
{
    public function __construct(
        private readonly ProjectManager $projectManager,
        private readonly ProjectResponseDtoFactory $factory,
    ) {
    }

    #[McpTool(
        name: 'create_project',
        description: '[Create Tool] Create a new project.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'name' => [
            'type' => 'string',
            'description' => 'Human-readable project name (1-255 characters). Examples: "Backend API", "Mobile App", "Website Redesign"',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Backend API',
        ],
        'prefix' => [
            'type' => 'string',
            'description' => 'Unique 3-character uppercase prefix (A-Z only). Used to auto-generate task codes: prefix + number (e.g., PRJ-1, PRJ-2). Must be unique across all projects.',
            'minLength' => 3,
            'maxLength' => 3,
            'pattern' => '^[A-Z]{3}$',
            'example' => 'PRJ',
        ],
        'aliases' => [
            'type' => 'array',
            'description' => 'Optional list of alternative names or aliases for searching (e.g. ["backend", "api", "rest"]). Help find the project by other names.',
            'items' => ['type' => 'string'],
            'example' => ['backend', 'api'],
        ],
    ], required: ['name', 'prefix'])]
    public function create(RequestContext $context): CallToolResult
    {
        return $this->handle($context, function (CreateProjectDto $dto): object {
            $task = $this->projectManager->create($dto);

            return $this->factory->create($task);
        });
    }

    #[McpTool(
        name: 'update_project',
        description: '[Update Tool] Update one or more fields of an existing project.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'name' => [
            'type' => ['string', 'null'],
            'description' => 'New project name (1-255 characters). Omit to keep the current value.',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Updated Backend API',
        ],
        'prefix' => [
            'type' => ['string', 'null'],
            'description' => 'New 3-character uppercase prefix (A-Z only). Must be unique. Changing prefix affects future task codes but not existing ones. Omit to keep the current value.',
            'minLength' => 3,
            'maxLength' => 3,
            'pattern' => '^[A-Z]{3}$',
            'example' => 'BAK',
        ],
        'aliases' => [
            'type' => ['array', 'null'],
            'description' => 'New list of aliases. Replaces the entire existing list. Pass empty array to clear aliases. Omit to keep the current value.',
            'items' => ['type' => 'string'],
            'example' => ['backend', 'rest-api', 'server'],
        ],
    ])]
    public function update(
        RequestContext $context,
        #[Schema(description: 'UUID of the project to update', format: 'uuid')]
        string $projectId,
    ): CallToolResult {
        return $this->handle($context, function (UpdateProjectDto $dto) use ($projectId): object {
            $project = $this->projectManager->get(Uuid::fromString($projectId));
            $this->projectManager->update($project, $dto);

            return $this->factory->create($project);
        });
    }
}
