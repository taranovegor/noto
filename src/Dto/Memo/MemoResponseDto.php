<?php

namespace App\Dto\Memo;

use App\Dto\Attachment\AttachmentResponseDto;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class MemoResponseDto
{
    /**
     * @param AttachmentResponseDto[]|null $attachments
     */
    public function __construct(
        #[Groups(['memo:read', 'memo:list'])]
        public Uuid $id,
        #[Groups(['memo:read', 'memo:list'])]
        public string $content,
        #[Groups(['memo:read', 'memo:list'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['memo:read', 'memo:list'])]
        public \DateTimeInterface $updatedAt,
        #[Groups(['memo:read'])]
        public ?array $attachments = null,
    ) {
    }
}
