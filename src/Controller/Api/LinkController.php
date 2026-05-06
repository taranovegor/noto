<?php

namespace App\Controller\Api;

use App\Dto\Link\CreateLinkDto;
use App\Dto\Link\LinkResponseDto;
use App\Factory\Link\LinkResponseDtoFactory;
use App\Service\Link\LinkManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/links', name: 'links_')]
#[OA\Tag(name: 'Links')]
final class LinkController extends AbstractController
{
    public function __construct(
        private readonly LinkManager $linkManager,
        private readonly LinkResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'create', methods: ['POST'])]
    #[OA\Post(
        description: 'Creates a directed link between two entities identified by their UUIDs.',
        summary: 'Create a link between two entities',
        requestBody: new OA\RequestBody(
            description: 'Link data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateLinkDto::class))
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Link successfully created',
                content: new OA\JsonContent(ref: new Model(type: LinkResponseDto::class, groups: ['link:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Source or target entity not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_CONFLICT,
                description: 'Link already exists',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function create(#[MapRequestPayload] CreateLinkDto $dto): JsonResponse
    {
        $link = $this->linkManager->create($dto);
        $responseDto = $this->responseDtoFactory->create($link);

        return $this->json($responseDto, Response::HTTP_CREATED, context: [
            AbstractNormalizer::GROUPS => ['link:read'],
        ]);
    }
}
