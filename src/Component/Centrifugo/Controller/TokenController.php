<?php

namespace App\Component\Centrifugo\Controller;

use App\Component\Centrifugo\CentrifugoInterface;
use App\Component\Centrifugo\Dto\ConnectionTokenDto;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[OA\Tag(name: 'Centrifugo')]
final class TokenController extends AbstractController
{
    public function __construct(
        private readonly CentrifugoInterface $centrifugo,
        #[Autowire('%env(CENTRIFUGO_WS_URL)%')] private readonly string $centrifugoUrl,
    ) {
    }

    #[Route('/connect', name: 'api_centrifugo_connect', methods: ['GET'])]
    #[OA\Get(
        description: 'Returns a fresh Centrifugo connection token for the authenticated user. Use this when the WebSocket token expires to reconnect without re-logging in.',
        summary: 'Get a WebSocket connection token',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Connection token',
                content: new OA\JsonContent(ref: new Model(type: ConnectionTokenDto::class))
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        $connectionToken = $this->centrifugo->generateConnectionToken($user);

        return $this->json([
            'userId' => $connectionToken->userId,
            'token' => $connectionToken->token,
            'url' => $this->centrifugoUrl,
        ]);
    }
}
