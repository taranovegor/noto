<?php

namespace App\Dto\Note;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class NoteResponseDto
{
    public function __construct(
        #[Groups(['note:read', 'note:list'])]
        public Uuid $id,
        #[Groups(['note:read', 'note:list'])]
        public string $content,
        #[Groups(['note:read', 'note:list'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['note:read', 'note:list'])]
        public \DateTimeInterface $updatedAt,
    ) {
    }
}
