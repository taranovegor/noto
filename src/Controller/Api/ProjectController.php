<?php

namespace App\Controller\Api;

use App\Component\Searcher\Model\Pagination;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Project\ProjectResponseDto;
use App\Dto\Project\SearchProjectDto;
use App\Entity\Project;
use App\Factory\Project\ProjectResponseDtoFactory;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects', name: 'project_')]
#[OA\Tag(name: 'Projects')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly SearcherInterface $searcher,
        private readonly ProjectResponseDtoFactory $responseDtoFactory,
    ) {
    }

    #[Route('', 'list', methods: ['GET'])]
    #[OA\Get(
        description: 'Lists projects with support for sorting, and pagination.',
        summary: 'List all projects',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Projects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: ProjectResponseDto::class))
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
    public function list(SearchProjectDto $criteria): JsonResponse
    {
        $searchResult = $this->searcher->search($criteria);

        $searchResult = $searchResult->map(fn (Project $project) => $this->responseDtoFactory->create($project));

        return $this->json($searchResult);
    }
}
