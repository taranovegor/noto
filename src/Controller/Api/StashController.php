<?php

namespace App\Controller\Api;

use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Stash\CreateStashDto;
use App\Dto\Stash\SearchStashDto;
use App\Dto\Stash\StashResponseDto;
use App\Dto\Stash\UpdateStashDto;
use App\Entity\Stash;
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
        /** @var SearcherInterface<Stash> */
        private readonly SearcherInterface $searcher,
        private readonly StashResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists stashes with support for filtering, sorting, and pagination.',
        summary: 'List all stashes',
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Number of records to return (max: 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
            new OA\Parameter(
                name: 'offset',
                description: 'Number of records to skip',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0)
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort by field(s). Prefix with - for descending. Separate multiple with ;. Available: createdAt, updatedAt',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[pinned]',
                description: 'Filter by pinned status. Operators: eq',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[expiresAt]',
                description: 'Filter by expiration date. Operators: gt, gte, lt, lte',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Stashes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: StashResponseDto::class, groups: ['pagination', 'stash:read', 'attachment:read']))
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
    public function list(SearchStashDto $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Stash $stash) => $this->responseDtoFactory->create($stash));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'stash:read', 'attachment:read'],
        ]);
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
        $responseDto = $this->responseDtoFactory->create($stash);

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
                content: new OA\JsonContent(ref: new Model(type: StashResponseDto::class, groups: ['stash:read', 'attachment:read']))
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

    #[Route('/{id}', 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        description: 'Deletes a stash and all its owned attachments (via Ownership links). Files in R2 are also deleted.',
        summary: 'Delete a stash',
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
                response: Response::HTTP_NO_CONTENT,
                description: 'Stash successfully deleted',
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Stash not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function delete(Uuid $id): JsonResponse
    {
        $stash = $this->stashManager->get($id);
        $now = new \DateTimeImmutable();

        if (null !== $stash->expiresAt && $stash->expiresAt < $now) {
            $this->stashManager->delete($stash);
        } else {
            $this->stashManager->expire($stash);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
