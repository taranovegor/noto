<?php

namespace App\Factory\Project;

use App\Dto\Project\ProjectResponseDto;
use App\Entity\Project;

class ProjectResponseDtoFactory
{
    public function create(Project $project): ProjectResponseDto
    {
        return new ProjectResponseDto(
            $project->id,
            $project->name,
            $project->prefix,
            $project->aliases,
            $project->createdAt,
        );
    }
}
