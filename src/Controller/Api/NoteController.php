<?php

namespace App\Controller\Api;

use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\NoteResponseDto;
use App\Dto\Note\SearchNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Note;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Note\NoteManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/notes', name: 'note_')]
#[OA\Tag(name: 'Notes')]
final class NoteController extends AbstractController
{
    public function __construct(
        private readonly NoteManager $noteManager,
        /** @var SearcherInterface<Note> */
        private readonly SearcherInterface $searcher,
        private readonly NoteResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists notes with support for sorting and pagination.',
        summary: 'List all notes',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Notes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: NoteResponseDto::class, groups: ['pagination', 'note:list']))
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
    public function list(SearchNoteDto $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Note $note) => $this->responseDtoFactory->create($note));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'note:list'],
        ]);
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a new note with the provided title and content. Returns the created note with 201 Created status.',
        summary: 'Create a new note',
        requestBody: new OA\RequestBody(
            description: 'Note creation data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateNoteDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Note successfully created',
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read']))
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
    public function create(#[MapRequestPayload] CreateNoteDto $dto): JsonResponse
    {
        $note = $this->noteManager->create($dto);
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['note:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Fetches a specific note by its unique identifier (UUID).',
        summary: 'Retrieve a note by ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Note unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Note retrieved successfully',
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read']))
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
        $note = $this->noteManager->get($id);
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['note:read'],
        ]);
    }

    #[Route('/{id}', 'update', methods: ['PATCH'])]
    #[OA\Patch(
        description: 'Partially updates a note with the provided fields. Only supplied fields will be updated.',
        summary: 'Update an existing note',
        requestBody: new OA\RequestBody(
            description: 'Note update data (all fields optional)',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateNoteDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Note unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Note successfully updated',
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read']))
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
    public function update(Uuid $id, #[MapRequestPayload] UpdateNoteDto $dto): JsonResponse
    {
        $note = $this->noteManager->get($id);
        $this->noteManager->update($note, $dto);
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['note:read'],
        ]);
    }
}
