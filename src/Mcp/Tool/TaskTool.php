<?php

namespace App\Mcp\Tool;

use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\UpdateTaskDto;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Service\Task\TaskManager;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;
use Symfony\Component\Uid\Uuid;

class TaskTool extends AbstractTool
{
    public function __construct(
        private readonly TaskManager $taskManager,
        private readonly TaskResponseDtoFactory $factory,
    ) {
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
            'enum' => ['low', 'medium', 'high', 'urgent'],
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
    ], required: ['name'])]
    public function create(RequestContext $context): CallToolResult
    {
        return $this->handle(
            'Task successfully created',
            $context,
            CreateTaskDto::class,
            function (CreateTaskDto $dto) {
                $task = $this->taskManager->create($dto);

                return $this->factory->create($task);
            },
        );
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
            'enum' => ['backlog', 'todo', 'in_progress', 'done'],
        ],
        'priority' => [
            'type' => ['string', 'null'],
            'description' => 'New priority level. Omit to keep the current value',
            'enum' => ['low', 'medium', 'high', 'urgent'],
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
        return $this->handle(
            'Task updated successfully',
            $context,
            UpdateTaskDto::class,
            function (UpdateTaskDto $dto) use ($taskId) {
                $task = $this->taskManager->get(Uuid::fromString($taskId));
                $this->taskManager->update($task, $dto);

                return $this->factory->create($task);
            },
        );
    }
}
