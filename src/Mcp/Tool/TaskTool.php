<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\SearchTaskDto;
use App\Dto\Task\UpdateTaskDto;
use App\Entity\Task;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Service\Task\TaskManager;
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
        description: 'Search and filter tasks with sorting and pagination support.',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'filter' => [
            'type' => ['object', 'null'],
            'description' => 'Filter conditions object',
            'properties' => [
                'projectId' => [
                    'type' => ['string', 'null'],
                    'description' => 'Project UUID to filter tasks by project',
                    'format' => 'uuid',
                ],
                'status' => [
                    'type' => ['string', 'null'],
                    'description' => 'Filter by task status using operators. Examples: "in:backlog,in_progress" or "neq:done"',
                ],
            ],
        ],
        'sort' => [
            'type' => ['string', 'null'],
            'description' => 'Sort fields separated by semicolon. Prefix field with - for DESC. Example: "-id;createdAt"',
            'default' => '',
        ],
        'limit' => [
            'type' => ['integer', 'null'],
            'description' => 'Maximum number of results to return',
            'default' => 20,
            'minimum' => 1,
        ],
        'offset' => [
            'type' => ['integer', 'null'],
            'description' => 'Number of results to skip (for pagination)',
            'default' => 0,
            'minimum' => 0,
        ],
    ])]
    public function search(
        RequestContext $context,
    ): CallToolResult {
        return $this->handle($context, function (SearchTaskDto $dto): CallToolResult {
            return $this->success(
                $this->searcher->search($dto)->map($this->factory->create(...))->getData(),
                [AbstractNormalizer::GROUPS => ['task:list']],
            );
        });
    }

    #[McpTool(
        name: 'create_task',
        description: 'Create a new task. Returns the created task with its ID and code (if assigned to a project).',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: false,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'projectId' => [
            'type' => ['string', 'null'],
            'description' => 'Project UUID to assign task to. Omit for unassigned task',
            'format' => 'uuid',
        ],
        'name' => [
            'type' => 'string',
            'description' => 'Task title',
            'minLength' => 1,
            'maxLength' => 255,
        ],
        'status' => [
            'type' => ['string', 'null'],
            'description' => 'Task status',
            'enum' => ['backlog', 'in_progress', 'done'],
        ],
        'priority' => [
            'type' => ['string', 'null'],
            'description' => 'Priority level',
            'enum' => ['low', 'medium', 'high'],
        ],
        'deadline' => [
            'type' => ['string', 'null'],
            'description' => 'Deadline as ISO 8601 date',
            'format' => 'datetime',
        ],
        'note' => [
            'type' => ['string', 'null'],
            'description' => 'Task notes or description',
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
        description: 'Update one or more fields of an existing task. Only provided fields are updated; omitted fields remain unchanged.',
        annotations: new ToolAnnotations(
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
        ),
    )]
    #[Schema(type: 'object', properties: [
        'projectId' => [
            'type' => ['string', 'null'],
            'description' => 'New task title. Omit to keep the current value',
            'format' => 'uuid',
        ],
        'name' => [
            'type' => ['string', 'null'],
            'description' => 'New task title. Omit to keep the current value',
            'minLength' => 1,
            'maxLength' => 255,
        ],
        'status' => [
            'type' => ['string', 'null'],
            'description' => 'New task status. Omit to keep the current value',
            'enum' => ['backlog', 'in_progress', 'done'],
        ],
        'priority' => [
            'type' => ['string', 'null'],
            'description' => 'New priority level. Omit to keep the current value',
            'enum' => ['low', 'medium', 'high'],
        ],
        'deadline' => [
            'type' => ['string', 'null'],
            'description' => 'New deadline as ISO 8601 date. Omit to keep the current value',
            'format' => 'datetime',
        ],
        'note' => [
            'type' => ['string', 'null'],
            'description' => 'New task notes. Omit to keep the current value',
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
