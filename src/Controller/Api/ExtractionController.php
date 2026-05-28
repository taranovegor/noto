<?php

namespace App\Controller\Api;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Extraction\CreateExtractionDto;
use App\Dto\Extraction\ExtractionResponseDto;
use App\Entity\Extraction;
use App\Factory\Extraction\ExtractionResponseDtoFactory;
use App\Service\Extraction\ExtractionManager;
use App\Service\Extraction\ExtractionSearchDefinition;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/extractions', name: 'extraction_')]
#[OA\Tag(name: 'Extractions')]
final class ExtractionController extends AbstractController
{
    public function __construct(
        private readonly ExtractionManager $manager,
        /** @var SearcherInterface<Extraction> */
        private readonly SearcherInterface $searcher,
        private readonly ExtractionResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists extractions with support for sorting and pagination.',
        summary: 'List all extractions',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extractions retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: ExtractionResponseDto::class, groups: ['pagination', 'extraction:list']))
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
        ]
    )]
    public function list(#[MapSearch(ExtractionSearchDefinition::class)] SearchQuery $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Extraction $e) => $this->responseDtoFactory->create($e));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'extraction:list'],
        ]);
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Starts an extraction from uploaded attachments into a target entity.',
        summary: 'Create a new extraction',
        requestBody: new OA\RequestBody(
            description: 'Extraction creation data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateExtractionDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extraction started',
                content: new OA\JsonContent(ref: new Model(type: ExtractionResponseDto::class, groups: ['extraction:read']))
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
        ]
    )]
    public function create(#[MapRequestPayload] CreateExtractionDto $dto): JsonResponse
    {
        $extraction = $this->manager->start($dto);
        $responseDto = $this->responseDtoFactory->create($extraction);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['extraction:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Fetches a specific extraction by its unique identifier (UUID).',
        summary: 'Retrieve an extraction by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Extraction unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extraction retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: ExtractionResponseDto::class, groups: ['extraction:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Extraction not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(Uuid $id): JsonResponse
    {
        $extraction = $this->manager->get($id);
        $responseDto = $this->responseDtoFactory->create($extraction);

        return $this->json($responseDto, context: [
            AbstractNormalizer::GROUPS => ['extraction:read'],
        ]);
    }
}
