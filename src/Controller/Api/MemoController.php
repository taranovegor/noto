<?php

namespace App\Controller\Api;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Memo\AttachMemoAttachmentsDto;
use App\Dto\Memo\CreateMemoDto;
use App\Dto\Memo\MemoResponseDto;
use App\Dto\Memo\UpdateMemoDto;
use App\Entity\Memo;
use App\Factory\Memo\MemoResponseDtoFactory;
use App\Service\Attachment\AttachmentManager;
use App\Service\Memo\MemoManager;
use App\Service\Memo\MemoSearchDefinition;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/memos', name: 'memo_')]
#[OA\Tag(name: 'Memos')]
final class MemoController extends AbstractController
{
    public function __construct(
        private readonly MemoManager $memoManager,
        private readonly AttachmentManager $attachmentManager,
        /** @var SearcherInterface<Memo> */
        private readonly SearcherInterface $searcher,
        private readonly MemoResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists memos with support for sorting and pagination.',
        summary: 'List all memos',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Memos retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: MemoResponseDto::class, groups: ['pagination', 'memo:list']))
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
                description: 'Bad request (invalid sort field)',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function list(#[MapSearch(MemoSearchDefinition::class)] SearchQuery $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Memo $memo) => $this->responseDtoFactory->create($memo));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'memo:list'],
        ]);
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a new memo. The first line starting with # is used as the title. Returns the created memo with 201 Created status.',
        summary: 'Create a new memo',
        requestBody: new OA\RequestBody(
            description: 'Memo creation data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateMemoDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Memo successfully created',
                content: new OA\JsonContent(ref: new Model(type: MemoResponseDto::class, groups: ['memo:read', 'attachment:read']))
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
    public function create(#[MapRequestPayload] CreateMemoDto $dto): JsonResponse
    {
        $memo = $this->memoManager->create($dto);
        $responseDto = $this->responseDtoFactory->create($memo);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['memo:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Fetches a specific memo by its unique identifier (UUID).',
        summary: 'Retrieve a memo by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Memo unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Memo retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: MemoResponseDto::class, groups: ['memo:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Memo not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
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
        $memo = $this->memoManager->get($id);
        $responseDto = $this->responseDtoFactory->create($memo);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['memo:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'update', methods: ['PATCH'])]
    #[OA\Patch(
        description: 'Partially updates a memo with the provided fields. Only supplied fields will be updated.',
        summary: 'Update an existing memo',
        requestBody: new OA\RequestBody(
            description: 'Memo update data (all fields optional)',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateMemoDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Memo unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Memo successfully updated',
                content: new OA\JsonContent(ref: new Model(type: MemoResponseDto::class, groups: ['memo:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Memo not found',
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
    public function update(Uuid $id, #[MapRequestPayload] UpdateMemoDto $dto): JsonResponse
    {
        $memo = $this->memoManager->get($id);
        $this->memoManager->update($memo, $dto);
        $responseDto = $this->responseDtoFactory->create($memo);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['memo:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}/attachments', 'attach_attachments', methods: ['POST'])]
    #[OA\Post(
        description: 'Attaches one or more already-uploaded attachments to the memo.',
        summary: 'Attach files to a memo',
        requestBody: new OA\RequestBody(
            description: 'Attachment IDs to attach',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: AttachMemoAttachmentsDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Memo unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Attachments linked, updated memo returned',
                content: new OA\JsonContent(ref: new Model(type: MemoResponseDto::class, groups: ['memo:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Memo not found',
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
    public function attachAttachments(Uuid $id, #[MapRequestPayload] AttachMemoAttachmentsDto $dto): JsonResponse
    {
        $memo = $this->memoManager->get($id);
        $this->memoManager->attach($memo, $dto);
        $responseDto = $this->responseDtoFactory->create($memo);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['memo:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}/attachments/{attachmentId}', 'detach_attachment', methods: ['DELETE'])]
    #[OA\Delete(
        description: 'Removes the ownership link between the memo and the attachment.',
        summary: 'Detach a file from a memo',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Memo unique identifier (UUID)',
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
                description: 'Memo or attachment not found',
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
        $memo = $this->memoManager->get($id);
        $attachment = $this->attachmentManager->get($attachmentId);
        $this->memoManager->detach($memo, $attachment);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
