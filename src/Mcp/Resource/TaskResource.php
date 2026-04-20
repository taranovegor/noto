<?php

namespace App\Mcp\Resource;

use App\Exception\EntityNotFoundException;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Service\Task\TaskManager;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Uid\Uuid;

class TaskResource extends AbstractResource
{
    public function __construct(
        private readonly TaskManager $taskManager,
        private readonly TaskResponseDtoFactory $factory,
    ) {
    }

    /**
     * Retrieve a task by its UUID.
     *
     * @param string $taskId UUID of the task to retrieve
     *
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[McpResourceTemplate(
        uriTemplate: 'task://{taskId}',
        name: 'task',
        description: 'Task details: id, name, status, priority, deadline, code, project, notes, timestamps.',
        mimeType: 'application/json',
    )]
    public function get(string $taskId): array
    {
        try {
            $task = $this->taskManager->get(Uuid::fromString($taskId));
        } catch (EntityNotFoundException) {
            throw new ResourceNotFoundException(sprintf('task://%s', $taskId));
        }

        $dto = $this->factory->create($task);

        return $this->normalize($dto);
    }
}
