<?php

namespace App\Service\Project;

use App\Entity\Project;
use App\Exception\EntityNotFoundException;
use App\Repository\ProjectRepository;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectManager
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {
    }

    public function get(Uuid $id): Project
    {
        return $this->projectRepository->find($id) ?? throw new EntityNotFoundException(Project::class, $id);
    }
}
