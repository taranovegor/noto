<?php

namespace App\Controller\Api;

use App\Dto\Ref\RefResponseDto;
use App\Factory\Ref\RefResponseDtoFactory;
use App\Service\Ref\RefManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Uid\Uuid;

#[Route('/refs', name: 'ref_')]
#[OA\Tag(name: 'Refs')]
final class RefController extends AbstractController
{
    public function __construct(
        private readonly RefManager $refManager,
        private readonly RefResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('/{id}', 'read', methods: ['GET'])]
    #[OA\Get(
        description: 'Resolves a reference UUID to its entity type.',
        summary: 'Resolve a reference by UUID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Reference unique identifier (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Reference resolved successfully',
                content: new OA\JsonContent(ref: new Model(type: RefResponseDto::class, groups: ['ref:read']))
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Reference not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function read(Uuid $id): JsonResponse
    {
        $ref = $this->refManager->get($id);
        $responseDto = $this->responseDtoFactory->create($ref);

        return $this->json($responseDto, context: [
            AbstractNormalizer::GROUPS => ['ref:read'],
        ]);
    }
}
