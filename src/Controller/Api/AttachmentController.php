<?php

namespace App\Controller\Api;

use App\Dto\Attachment\AttachmentDownloadResponseDto;
use App\Dto\Attachment\AttachmentDto;
use App\Dto\Attachment\AttachmentResponseDto;
use App\Dto\Attachment\AttachmentUploadResponseDto;
use App\Factory\Attachment\AttachmentDownloadResponseDtoFactory;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Attachment\AttachmentUploadResponseDtoFactory;
use App\Service\Attachment\AttachmentManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/attachments', name: 'attachments_')]
#[OA\Tag(name: 'Attachments')]
final class AttachmentController extends AbstractController
{
    public function __construct(
        private readonly AttachmentManager $attachmentManager,
        private readonly AttachmentUploadResponseDtoFactory $attachmentUploadResponseDtoFactory,
        private readonly AttachmentResponseDtoFactory $responseDtoFactory,
        private readonly AttachmentDownloadResponseDtoFactory $downloadResponseDtoFactory,
    ) {
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates an attachment record and returns a pre-signed PUT URL for direct upload to R2.',
        summary: 'Create attachment and get upload URL',
        requestBody: new OA\RequestBody(
            description: 'Attachment metadata',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: AttachmentDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Attachment created, upload URL ready',
                content: new OA\JsonContent(ref: new Model(type: AttachmentUploadResponseDto::class, groups: ['attachment:read']))
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
    public function create(#[MapRequestPayload] AttachmentDto $dto): JsonResponse
    {
        $attachment = $this->attachmentManager->create($dto);
        $responseDto = $this->attachmentUploadResponseDtoFactory->create($attachment);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['attachment:read'],
        ]);
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Returns attachment metadata by ID.',
        summary: 'Get attachment metadata',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Attachment UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Attachment metadata',
                content: new OA\JsonContent(ref: new Model(type: AttachmentResponseDto::class, groups: ['attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Attachment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(Uuid $id): JsonResponse
    {
        $attachment = $this->attachmentManager->get($id);
        $responseDto = $this->responseDtoFactory->create($attachment);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['attachment:read'],
        ]);
    }

    #[Route('/{id}/confirm', 'confirm', methods: ['POST'])]
    #[OA\Post(
        description: 'Confirms the file was uploaded to R2. Verifies the object exists in storage and marks the attachment as uploaded.',
        summary: 'Confirm file upload',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Attachment UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Upload confirmed',
                content: new OA\JsonContent(ref: new Model(type: AttachmentResponseDto::class, groups: ['attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Attachment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_CONFLICT,
                description: 'File not found in storage',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function confirm(Uuid $id): JsonResponse
    {
        $attachment = $this->attachmentManager->get($id);
        $this->attachmentManager->confirm($attachment);
        $responseDto = $this->responseDtoFactory->create($attachment);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['attachment:read'],
        ]);
    }

    #[Route('/{id}/download', 'download', methods: ['GET'])]
    #[OA\Get(
        description: 'Returns a pre-signed GET URL for downloading/viewing the file from R2.',
        summary: 'Get download URL',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Attachment UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Download URL generated',
                content: new OA\JsonContent(ref: new Model(type: AttachmentDownloadResponseDto::class, groups: ['attachment:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Attachment not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function download(Uuid $id): JsonResponse
    {
        $attachment = $this->attachmentManager->get($id);
        $responseDto = $this->downloadResponseDtoFactory->create($attachment);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['attachment:read'],
        ]);
    }
}
