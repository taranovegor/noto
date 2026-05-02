<?php

namespace App\Controller\Api;

use App\Dto\User\UserResponseDto;
use App\Entity\User;
use App\Factory\User\UserResponseDtoFactory;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/users', name: 'user_')]
#[OA\Tag(name: 'Users')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('/me', 'me', methods: ['GET'])]
    #[OA\Get(
        description: 'Get the currently authenticated user profile',
        summary: 'Get current user',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current user data',
                content: new OA\JsonContent(ref: new Model(type: UserResponseDto::class, groups: ['user:read']))
            ),
        ]
    )]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        $responseDto = $this->responseDtoFactory->create($user);

        return $this->json($responseDto, Response::HTTP_OK, context: [
            AbstractNormalizer::GROUPS => ['user:read'],
        ]);
    }
}
