<?php

namespace App\Dto\Notebook;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class NotebookResponseDto
{
    public function __construct(
        #[Groups(['notebook:read', 'notebook:list'])]
        public Uuid $id,
        #[Groups(['notebook:read', 'notebook:list'])]
        public string $title,
        #[Groups(['notebook:read', 'notebook:list'])]
        public string $description,
        #[Groups(['notebook:read', 'notebook:list'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['notebook:read', 'notebook:list'])]
        public \DateTimeInterface $updatedAt,
    ) {
    }
}
