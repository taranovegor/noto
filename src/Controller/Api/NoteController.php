<?php

namespace App\Controller\Api;

use App\Component\Searcher\Attribute\MapSearch;
use App\Component\Searcher\Dto\SearchQuery;
use App\Component\Searcher\Model\Pagination;
use App\Dto\Note\AttachNoteAttachmentsDto;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\NoteResponseDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Attachment;
use App\Entity\Note;
use App\Entity\Notebook;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Note\NoteFinder;
use App\Service\Note\NoteManager;
use App\Service\Note\NoteSearchDefinition;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/notebooks/{notebookId}/notes', name: 'note_')]
#[OA\Tag(name: 'Notes')]
final class NoteController extends AbstractController
{
    public function __construct(
        private readonly NoteManager $noteManager,
        /** @var NoteFinder<Note> */
        private readonly NoteFinder $noteFinder,
        private readonly NoteResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists notes in a notebook with support for sorting and pagination.',
        summary: 'List notes in a notebook',
        parameters: [
            new OA\Parameter(
                name: 'notebookId',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
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
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Bad request (invalid sort field)',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function list(
        #[MapEntity(id: 'notebookId')] Notebook $notebook,
        #[MapSearch(NoteSearchDefinition::class)] SearchQuery $criteria,
    ): JsonResponse {
        $searchResult = $this->noteFinder->inNotebook($notebook, $criteria);

        $searchResult = $searchResult->map(fn (Note $note) => $this->responseDtoFactory->create($note));

        return $this->json($searchResult, context: [
            AbstractNormalizer::GROUPS => ['pagination', 'note:list'],
        ]);
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a new note in the specified notebook. Returns the created note with 201 Created status.',
        summary: 'Create a new note',
        requestBody: new OA\RequestBody(
            description: 'Note creation data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateNoteDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'notebookId',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Note successfully created',
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
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
    public function create(
        #[MapEntity(id: 'notebookId')] Notebook $notebook,
        #[MapRequestPayload] CreateNoteDto $dto,
    ): JsonResponse {
        $note = $this->noteManager->create($notebook, $dto);
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['note:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Fetches a specific note by its unique identifier (UUID).',
        summary: 'Retrieve a note by ID',
        parameters: [
            new OA\Parameter(
                name: 'notebookId',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
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
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook or note not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(
        #[MapEntity(mapping: ['notebookId' => 'notebook', 'id' => 'id'])] Note $note,
    ): JsonResponse {
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['note:read', 'attachment:read'],
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
                name: 'notebookId',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
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
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook or note not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function update(
        #[MapEntity(mapping: ['notebookId' => 'notebook', 'id' => 'id'])] Note $note,
        #[MapRequestPayload] UpdateNoteDto $dto,
    ): JsonResponse {
        $this->noteManager->update($note, $dto);
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['note:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}/attachments', 'attach_attachments', methods: ['POST'])]
    #[OA\Post(
        description: 'Attaches one or more already-uploaded attachments to the note.',
        summary: 'Attach files to a note',
        requestBody: new OA\RequestBody(
            description: 'Attachment IDs to attach',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: AttachNoteAttachmentsDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'notebookId',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
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
                description: 'Attachments linked, updated note returned',
                content: new OA\JsonContent(ref: new Model(type: NoteResponseDto::class, groups: ['note:read', 'attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Notebook or note not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function attachAttachments(
        #[MapEntity(mapping: ['notebookId' => 'notebook', 'id' => 'id'])] Note $note,
        #[MapRequestPayload] AttachNoteAttachmentsDto $dto,
    ): JsonResponse {
        $this->noteManager->attach($note, $dto);
        $responseDto = $this->responseDtoFactory->create($note);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['note:read', 'attachment:read'],
        ]);
    }

    #[Route('/{id}/attachments/{attachmentId}', 'detach_attachment', methods: ['DELETE'])]
    #[OA\Delete(
        description: 'Removes the ownership link between the note and the attachment.',
        summary: 'Detach a file from a note',
        parameters: [
            new OA\Parameter(
                name: 'notebookId',
                description: 'Notebook unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'id',
                description: 'Note unique identifier (UUID)',
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
                description: 'Notebook, note, or attachment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function detachAttachment(
        #[MapEntity(mapping: ['notebookId' => 'notebook', 'id' => 'id'])] Note $note,
        #[MapEntity(id: 'attachmentId')] Attachment $attachment,
    ): JsonResponse {
        $this->noteManager->detach($note, $attachment);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
