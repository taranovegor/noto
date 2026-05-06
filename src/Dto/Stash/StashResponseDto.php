<?php

namespace App\Dto\Stash;

use App\Dto\Attachment\AttachmentResponseDto;
use App\Dto\Attachment\AttachmentUploadResponseDto;
use App\Enum\StashType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class StashResponseDto
{
    /**
     * @param array<int, AttachmentResponseDto|AttachmentUploadResponseDto>|null $attachments
     */
    public function __construct(
        #[Groups(['stash:read'])]
        public Uuid $id,
        #[Groups(['stash:read'])]
        public StashType $type,
        #[Groups(['stash:read'])]
        public ?string $content,
        #[Groups(['stash:read'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['stash:read'])]
        public ?\DateTimeInterface $expiresAt,
        #[Groups(['stash:read'])]
        public bool $pinned,
        #[Groups(['stash:read'])]
        public ?array $attachments = null,
    ) {
    }
}
