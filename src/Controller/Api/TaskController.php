<?php

namespace App\Controller\Api;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Task\AttachTaskAttachmentsDto;
use App\Dto\Task\CreateTaskDto;
use App\Dto\Task\TaskResponseDto;
use App\Dto\Task\UpdateTaskDto;
use App\Entity\Task;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Service\Attachment\AttachmentManager;
use App\Service\Task\TaskManager;
use App\Service\Task\TaskSearchDefinition;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/tasks', name: 'task_')]
#[OA\Tag(name: 'Tasks')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskManager $taskManager,
        private readonly AttachmentManager $attachmentManager,
        /** @var SearcherInterface<Task> */
        private readonly SearcherInterface $searcher,
        private readonly TaskResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists tasks with support for filtering, sorting, and pagination.',
        summary: 'List all tasks',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Tasks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: TaskResponseDto::class, groups: ['pagination', 'task:list']))
                        ),
                        new OA\Property(
                            property: 'pagination',
                            ref: new Model(type: Pagination::class),
                            type: 'object',
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Bad request (invalid filter/sort field or operator)',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function list(#[MapSearch(TaskSearchDefinition::class)] SearchQuery $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Task $task) => $this->responseDtoFactory->create($task));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'task:list'],
        ]);
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a new task with the provided details. Returns the created task with 201 Created status.',
        summary: 'Create a new task',
        requestBody: new OA\RequestBody(
            description: 'Task creation data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateTaskDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Task successfully created',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class, groups: ['task:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Bad request',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function create(#[MapRequestPayload] CreateTaskDto $dto): JsonResponse
    {
        $task = $this->taskManager->create($dto);
        $responseDto = $this->responseDtoFactory->create($task);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['task:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Fetches a specific task by its unique identifier (UUID).',
        summary: 'Retrieve a task by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Task unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Task retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class, groups: ['task:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Bad request',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(Uuid $id): JsonResponse
    {
        $task = $this->taskManager->get($id);
        $responseDto = $this->responseDtoFactory->create($task);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['task:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'update', methods: ['PATCH'])]
    #[OA\Patch(
        description: 'Partially updates a task with the provided fields. Only supplied fields will be updated.',
        summary: 'Update an existing task',
        requestBody: new OA\RequestBody(
            description: 'Task update data (all fields optional)',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateTaskDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Task unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Task successfully updated',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class, groups: ['task:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Task not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function update(Uuid $id, #[MapRequestPayload] UpdateTaskDto $dto): JsonResponse
    {
        $task = $this->taskManager->get($id);
        $this->taskManager->update($task, $dto);
        $responseDto = $this->responseDtoFactory->create($task);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['task:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}/attachments', 'attach_attachments', methods: ['POST'])]
    #[OA\Post(
        description: 'Attaches one or more already-uploaded attachments to the task.',
        summary: 'Attach files to a task',
        requestBody: new OA\RequestBody(
            description: 'Attachment IDs to attach',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: AttachTaskAttachmentsDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Task unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Attachments linked, updated task returned',
                content: new OA\JsonContent(ref: new Model(type: TaskResponseDto::class, groups: ['task:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Task not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function attachAttachments(Uuid $id, #[MapRequestPayload] AttachTaskAttachmentsDto $dto): JsonResponse
    {
        $task = $this->taskManager->get($id);
        $this->taskManager->attach($task, $dto);
        $responseDto = $this->responseDtoFactory->create($task);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['task:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}/attachments/{attachmentId}', 'detach_attachment', methods: ['DELETE'])]
    #[OA\Delete(
        description: 'Removes the ownership link between the task and the attachment.',
        summary: 'Detach a file from a task',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Task unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'attachmentId',
                description: 'Attachment unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Attachment detached'
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Task or attachment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function detachAttachment(Uuid $id, Uuid $attachmentId): JsonResponse
    {
        $task = $this->taskManager->get($id);
        $attachment = $this->attachmentManager->get($attachmentId);
        $this->taskManager->detach($task, $attachment);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
