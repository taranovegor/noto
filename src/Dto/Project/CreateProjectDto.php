<?php

namespace App\Dto\Project;

use App\Component\Validator\Constraint\EntityExists;
use App\Entity\Project;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProjectDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[EntityExists(entityClass: Project::class, field: 'prefix')]
        #[Assert\NotBlank]
        public string $prefix,
        public array $aliases = [],
    ) {
    }
}
