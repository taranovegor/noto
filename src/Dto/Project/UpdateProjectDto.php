<?php

namespace App\Dto\Project;

use App\Component\Validator\Constraint\EntityExists;
use App\Entity\Project;

final readonly class UpdateProjectDto
{
    /**
     * @param array<string>|null $aliases
     */
    public function __construct(
        public ?string $name = null,
        #[EntityExists(entityClass: Project::class, field: 'prefix')]
        public ?string $prefix = null,
        public ?array $aliases = null,
    ) {
    }
}
