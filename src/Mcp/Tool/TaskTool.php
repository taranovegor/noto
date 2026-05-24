<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\UpdateTaskDto;
use App\Entity\Task;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Service\Task\TaskManager;
use App\Service\Task\TaskSearchDefinition;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

class TaskTool extends AbstractTool
{
    /**
     * @param SearcherInterface<Task> $searcher
     */
    public function __construct(
        private readonly TaskManager $taskManager,
        private readonly TaskResponseDtoFactory $factory,
        private readonly SearcherInterface $searcher,
    ) {
    }

    #[McpTool(
        name: 'search_tasks',
        description: '[Query Tool] Search and filter tasks with optional sorting and pagination.',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'filter' => [
            'type' => ['object', 'null'],
            'description' => 'Filter conditions object with field-value pairs. Supports operators: eq, neq, in, notIn, gt, gte, lt, lte (see instructions for examples).',
            'properties' => [
                'query' => [
                    'type' => ['string', 'null'],
                    'description' => 'Search query text (full-text search)',
                    'example' => 'implement auth',
                ],
                'projectId' => [
                    'type' => ['string', 'null'],
                    'description' => 'Filter by project UUID. Example: "eq:019d87b4-ae7b-7e2a-a0a3-3774fab76a3e" or just the UUID for exact match',
                    'format' => 'uuid',
                    'example' => '019d87b4-ae7b-7e2a-a0a3-3774fab76a3e',
                ],
                'status' => [
                    'type' => ['string', 'null'],
                    'description' => 'Filter by status using operators. Examples: "in:backlog,in_progress", "neq:done"',
                    'example' => 'in:backlog,in_progress',
                ],
            ],
        ],
        'sort' => [
            'type' => ['string', 'null'],
            'description' => 'Sort fields separated by semicolon. Prefix with - for DESC, omit or use no prefix for ASC. Examples: "-createdAt" (newest first), "priority;-id" (by priority ASC, then by ID DESC)',
            'example' => '-createdAt;priority',
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
        return $this->handle($context, function (#[MapSearch(TaskSearchDefinition::class)] SearchQuery $query): CallToolResult {
            return $this->success(
                $this->searcher->search($query)->map($this->factory->create(...))->getData(),
                [AbstractNormalizer::GROUPS => ['task:list']],
            );
        });
    }

    #[McpTool(
        name: 'create_task',
        description: '[Create Tool] Create a new task.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'projectId' => [
            'type' => ['string', 'null'],
            'description' => 'Project UUID to assign task to. If provided, task will get auto-generated code like PRJ-42. Omit for unassigned task.',
            'format' => 'uuid',
            'example' => '019d87b4-ae7b-7e2a-a0a3-3774fab76a3e',
        ],
        'name' => [
            'type' => 'string',
            'description' => 'Task title (1-255 characters)',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Implement user authentication',
        ],
        'status' => [
            'type' => ['string', 'null'],
            'description' => 'Task status. Allowed values: backlog, in_progress, done',
            'enum' => ['backlog', 'in_progress', 'done'],
            'example' => 'backlog',
        ],
        'priority' => [
            'type' => ['string', 'null'],
            'description' => 'Priority level. Allowed values: low, medium, high',
            'enum' => ['low', 'medium', 'high'],
            'example' => 'high',
        ],
        'deadline' => [
            'type' => ['string', 'null'],
            'description' => 'Deadline as ISO 8601 date string (YYYY-MM-DD)',
            'format' => 'date',
            'example' => '2026-05-20',
        ],
        'note' => [
            'type' => ['string', 'null'],
            'description' => 'Task notes, description, or additional context',
            'example' => 'OAuth2 with JWT tokens',
        ],
    ], required: ['name', 'status'])]
    public function create(RequestContext $context): CallToolResult
    {
        return $this->handle($context, function (CreateTaskDto $dto): object {
            $task = $this->taskManager->create($dto);

            return $this->success($this->factory->create($task), [
                AbstractNormalizer::GROUPS => ['task:read'],
            ]);
        });
    }

    #[McpTool(
        name: 'update_task',
        description: '[Update Tool] Update one or more fields of an existing task.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'projectId' => [
            'type' => ['string', 'null'],
            'description' => 'Change which project this task belongs to (changes auto-generated code). Pass null to unassign. Omit to keep the current value.',
            'format' => 'uuid',
            'example' => '019d87b4-ae7b-7e2a-a0a3-3774fab76a3e',
        ],
        'name' => [
            'type' => ['string', 'null'],
            'description' => 'New task title (1-255 characters). Omit to keep the current value.',
            'minLength' => 1,
            'maxLength' => 255,
            'example' => 'Review authentication implementation',
        ],
        'status' => [
            'type' => ['string', 'null'],
            'description' => 'New status. Allowed values: backlog, in_progress, done. Omit to keep the current value.',
            'enum' => ['backlog', 'in_progress', 'done'],
            'example' => 'in_progress',
        ],
        'priority' => [
            'type' => ['string', 'null'],
            'description' => 'New priority. Allowed values: low, medium, high. Omit to keep the current value.',
            'enum' => ['low', 'medium', 'high'],
            'example' => 'medium',
        ],
        'deadline' => [
            'type' => ['string', 'null'],
            'description' => 'New deadline as ISO 8601 date (YYYY-MM-DD), or null to remove deadline. Omit to keep the current value.',
            'format' => 'date',
            'example' => '2026-05-15',
        ],
        'note' => [
            'type' => ['string', 'null'],
            'description' => 'Update task notes/description. Omit to keep the current value.',
            'example' => 'Need code review before deployment',
        ],
    ])]
    public function update(
        RequestContext $context,
        #[Schema(description: 'UUID of the task to update', format: 'uuid')]
        string $taskId,
    ): CallToolResult {
        return $this->handle($context, function (UpdateTaskDto $dto) use ($taskId): object {
            $task = $this->taskManager->get(Uuid::fromString($taskId));
            $this->taskManager->update($task, $dto);

            return $this->success($this->factory->create($task), [
                AbstractNormalizer::GROUPS => ['task:read'],
            ]);
        });
    }
}
