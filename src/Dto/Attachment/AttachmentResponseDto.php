<?php

namespace App\Dto\Attachment;

use App\Enum\AttachmentStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class AttachmentResponseDto
{
    public function __construct(
        #[Groups(['attachment:read'])]
        public Uuid $id,
        #[Groups(['attachment:read'])]
        public string $originFilename,
        #[Groups(['attachment:read'])]
        public string $mimeType,
        #[Groups(['attachment:read'])]
        public int $size,
        #[Groups(['attachment:read'])]
        public AttachmentStatus $status,
        #[Groups(['attachment:read'])]
        public \DateTimeInterface $createdAt,
    ) {
    }
}
