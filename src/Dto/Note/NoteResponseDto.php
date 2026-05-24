<?php

namespace App\Dto\Note;

use App\Dto\Attachment\AttachmentResponseDto;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class NoteResponseDto
{
    /**
     * @param AttachmentResponseDto[]|null $attachments
     */
    public function __construct(
        #[Groups(['note:read', 'note:list'])]
        public Uuid $id,
        #[Groups(['note:read', 'note:list'])]
        public Uuid $notebookId,
        #[Groups(['note:read', 'note:list'])]
        public string $title,
        #[Groups(['note:read', 'note:list'])]
        public string $content,
        #[Groups(['note:read', 'note:list'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['note:read', 'note:list'])]
        public \DateTimeInterface $updatedAt,
        #[Groups(['note:read'])]
        public ?array $attachments = null,
    ) {
    }
}
