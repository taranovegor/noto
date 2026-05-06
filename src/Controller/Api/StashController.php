<?php

namespace App\Controller\Api;

use App\Dto\Stash\CreateStashDto;
use App\Dto\Stash\StashResponseDto;
use App\Dto\Stash\UpdateStashDto;
use App\Factory\Stash\StashResponseDtoFactory;
use App\Service\Stash\StashManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/stashes', name: 'stashes_')]
#[OA\Tag(name: 'Stashes')]
final class StashController extends AbstractController
{
    public function __construct(
        private readonly StashManager $stashManager,
        private readonly StashResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a new stash. If type is "file", attachments are created and linked automatically.',
        summary: 'Create a stash',
        requestBody: new OA\RequestBody(
            description: 'Stash data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateStashDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Stash created',
                content: new OA\JsonContent(ref: new Model(type: StashResponseDto::class, groups: ['stash:read']))
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function create(#[MapRequestPayload] CreateStashDto $dto): JsonResponse
    {
        $stash = $this->stashManager->create($dto);
        $responseDto = $this->responseDtoFactory->createWithUploadUrls($stash);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['stash:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Returns stash metadata and file information if it is a file stash.',
        summary: 'Get stash by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Stash UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Stash retrieved',
                content: new OA\JsonContent(ref: new Model(type: StashResponseDto::class, groups: ['stash:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Stash not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(Uuid $id): JsonResponse
    {
        $stash = $this->stashManager->get($id);
        $responseDto = $this->responseDtoFactory->create($stash);

        return $this->json($responseDto, context: [
            AbstractNormalizer::GROUPS => ['stash:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'update', methods: ['PATCH'])]
    #[OA\Patch(
        description: 'Partially updates a stash. Currently supports toggling the pinned flag.',
        summary: 'Update a stash',
        requestBody: new OA\RequestBody(
            description: 'Fields to update',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateStashDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Stash UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Stash updated',
                content: new OA\JsonContent(ref: new Model(type: StashResponseDto::class, groups: ['stash:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Stash not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function update(Uuid $id, #[MapRequestPayload] UpdateStashDto $dto): JsonResponse
    {
        $stash = $this->stashManager->get($id);
        $this->stashManager->update($stash, $dto);
        $responseDto = $this->responseDtoFactory->create($stash);

        return $this->json($responseDto, context: [
            AbstractNormalizer::GROUPS => ['stash:read', 'attachment:read'],
        ]);
    }
}
