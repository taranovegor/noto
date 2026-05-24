<?php

namespace App\Controller\Api;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Notebook\CreateNotebookDto;
use App\Dto\Notebook\NotebookResponseDto;
use App\Dto\Notebook\UpdateNotebookDto;
use App\Entity\Notebook;
use App\Factory\Notebook\NotebookResponseDtoFactory;
use App\Service\Notebook\NotebookManager;
use App\Service\Notebook\NotebookSearchDefinition;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/notebooks', name: 'notebook_')]
#[OA\Tag(name: 'Notebooks')]
final class NotebookController extends AbstractController
{
    public function __construct(
        private readonly NotebookManager $notebookManager,
        /** @var SearcherInterface<Notebook> */
        private readonly SearcherInterface $searcher,
        private readonly NotebookResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists notebooks with support for sorting and pagination.',
        summary: 'List all notebooks',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Notebooks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: NotebookResponseDto::class, groups: ['pagination', 'notebook:list']))
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
    public function list(#[MapSearch(NotebookSearchDefinition::class)] SearchQuery $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Notebook $notebook) => $this->responseDtoFactory->create($notebook));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'notebook:list'],
        ]);
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a new notebook. Returns the created notebook with 201 Created status.',
        summary: 'Create a new notebook',
        requestBody: new OA\RequestBody(
            description: 'Notebook creation data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateNotebookDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Notebook successfully created',
                content: new OA\JsonContent(ref: new Model(type: NotebookResponseDto::class, groups: ['notebook:read']))
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
    public function create(#[MapRequestPayload] CreateNotebookDto $dto): JsonResponse
    {
        $notebook = $this->notebookManager->create($dto);
        $responseDto = $this->responseDtoFactory->create($notebook);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['notebook:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Fetches a specific notebook by its unique identifier (UUID).',
        summary: 'Retrieve a notebook by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Notebook retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: NotebookResponseDto::class, groups: ['notebook:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(#[MapEntity] Notebook $notebook): JsonResponse
    {
        $responseDto = $this->responseDtoFactory->create($notebook);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['notebook:read'],
        ]);
    }

    #[Route('/{id}', 'update', methods: ['PATCH'])]
    #[OA\Patch(
        description: 'Partially updates a notebook with the provided fields. Only supplied fields will be updated.',
        summary: 'Update an existing notebook',
        requestBody: new OA\RequestBody(
            description: 'Notebook update data (all fields optional)',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateNotebookDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Notebook successfully updated',
                content: new OA\JsonContent(ref: new Model(type: NotebookResponseDto::class, groups: ['notebook:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook not found',
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
    public function update(
        #[MapEntity] Notebook $notebook,
        #[MapRequestPayload] UpdateNotebookDto $dto,
    ): JsonResponse {
        $this->notebookManager->update($notebook, $dto);
        $responseDto = $this->responseDtoFactory->create($notebook);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['notebook:read'],
        ]);
    }
}
