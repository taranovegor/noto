<?php

namespace App\Mcp\Resource;

use App\Exception\EntityNotFoundException;
use App\Factory\Project\ProjectResponseDtoFactory;
use App\Service\Project\ProjectManager;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Uid\Uuid;

class ProjectResource extends AbstractResource
{
    public function __construct(
        private readonly ProjectManager $projectManager,
        private readonly ProjectResponseDtoFactory $factory,
    ) {
    }

    /**
     * Retrieve a project by its UUID.
     *
     * @param string $projectId UUID of the project to retrieve
     *
     * @return array<string, mixed>
     *
     * @throws ExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[McpResourceTemplate(
        uriTemplate: 'project://{projectId}',
        name: 'project',
        description: 'Project details: id, name, 3-char task prefix (e.g. PRJ → PRJ-1), aliases, createdAt.',
        mimeType: 'application/json',
    )]
    public function get(string $projectId): array
    {
        try {
            $project = $this->projectManager->get(Uuid::fromString($projectId));
        } catch (EntityNotFoundException) {
            throw new ResourceNotFoundException(sprintf('project://%s', $projectId));
        }

        $dto = $this->factory->create($project);

        return $this->normalize($dto);
    }
}
