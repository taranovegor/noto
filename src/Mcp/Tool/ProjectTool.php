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
        description: 'Create a new project. Returns the created project with its ID.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'name' => [
            'type' => 'string',
            'description' => 'Human-readable project name (e.g. "Backend API")',
            'minLength' => 1,
            'maxLength' => 255,
        ],
        'prefix' => [
            'type' => 'string',
            'description' => 'Unique 3-character uppercase prefix used to generate task codes',
            'minLength' => 3,
            'maxLength' => 3,
            'pattern' => '^[A-Z]{3}$',
        ],
        'aliases' => [
            'type' => 'array',
            'description' => 'Alternative names or slugs for this project (e.g. ["backend", "api"])',
            'items' => ['type' => 'string'],
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
        description: 'Update one or more fields of an existing project. Only provided fields are updated; omitted fields remain unchanged.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'name' => [
            'type' => ['string', 'null'],
            'description' => 'New project name. Omit to keep the current value',
            'minLength' => 1,
            'maxLength' => 255,
        ],
        'prefix' => [
            'type' => ['string', 'null'],
            'description' => 'New 3-character uppercase prefix. Omit to keep the current value',
            'minLength' => 3,
            'maxLength' => 3,
            'pattern' => '^[A-Z]{3}$',
        ],
        'aliases' => [
            'type' => ['array', 'null'],
            'description' => 'New list of aliases. Replaces the existing list entirely. Omit to keep the current value',
            'items' => ['type' => 'string'],
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
