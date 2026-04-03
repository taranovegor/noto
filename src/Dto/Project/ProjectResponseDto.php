<?php

namespace App\Dto\Project;

use Symfony\Component\Uid\Uuid;

readonly class ProjectResponseDto
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $prefix,
        public array $aliases,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
