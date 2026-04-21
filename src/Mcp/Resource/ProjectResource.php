<?php

namespace App\Mcp\Resource;

use App\Component\Searcher\Model\PaginationDetails;
use App\Component\Searcher\SearcherInterface;
use App\Dto\Project\SearchProjectDto;
use App\Entity\Project;
use App\Exception\EntityNotFoundException;
use App\Factory\Project\ProjectResponseDtoFactory;
use App\Service\Project\ProjectManager;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Schema\Content\TextResourceContents;
use Symfony\Component\Uid\Uuid;

class ProjectResource extends AbstractResource
{
    /**
     * @param SearcherInterface<Project> $searcher
     */
    public function __construct(
        private readonly ProjectManager $projectManager,
        private readonly ProjectResponseDtoFactory $factory,
        private readonly SearcherInterface $searcher,
    ) {
    }

    /**
     * List all projects as resources.
     *
     * @return list<TextResourceContents>
     */
    #[McpResource(
        uri: 'project://',
        name: 'projects',
        description: 'List of all projects',
    )]
    public function list(): array
    {
        $projects = $this->searcher->search(new SearchProjectDto([], [], PaginationDetails::unlimited()));

        return $projects->map(
            fn (Project $p) => $this->textResource("project://{$p->id}", $this->factory->create($p)),
        )->getData();
    }

    /**
     * Retrieve a project by its UUID.
     *
     * @param string $projectId UUID of the project to retrieve
     */
    #[McpResourceTemplate(
        uriTemplate: 'project://{projectId}',
        name: 'project',
        description: 'Project details: id, name, 3-char task prefix (e.g. PRJ → PRJ-1), aliases, createdAt.',
        mimeType: 'application/json',
    )]
    public function get(string $projectId): TextResourceContents
    {
        try {
            $project = $this->projectManager->get(Uuid::fromString($projectId));
        } catch (EntityNotFoundException) {
            throw new ResourceNotFoundException(sprintf('project://%s', $projectId));
        }

        $dto = $this->factory->create($project);

        return $this->textResource("project://{$dto->id}", $dto);
    }
}
