<?php

namespace App\Service\Project;

use App\Dto\Project\CreateProjectDto;
use App\Dto\Project\UpdateProjectDto;
use App\Entity\Project;
use App\Exception\EntityNotFoundException;
use App\Repository\ProjectRepository;
use App\Service\Flusher;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectManager
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateProjectDto $dto): Project
    {
        $project = new Project($dto->name, $dto->prefix);
        $project->aliases = $dto->aliases;

        $this->projectRepository->add($project);

        $this->flusher->flush();

        return $project;
    }

    public function get(Uuid $id): Project
    {
        return $this->projectRepository->find($id) ?? throw new EntityNotFoundException(Project::class, $id);
    }

    public function update(Project $project, UpdateProjectDto $dto): void
    {
        if (null !== $dto->name) {
            $project->name = $dto->name;
        }

        if (null !== $dto->prefix) {
            $project->prefix = $dto->prefix;
        }

        if (null !== $dto->aliases) {
            $project->aliases = $dto->aliases;
        }

        $this->flusher->flush();
    }
}
