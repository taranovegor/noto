<?php

namespace App\Dto\Attachment;

use App\Enum\AttachmentStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class AttachmentDownloadResponseDto extends AttachmentResponseDto
{
    public function __construct(
        Uuid $id,
        string $originFilename,
        string $mimeType,
        int $size,
        AttachmentStatus $status,
        \DateTimeInterface $createdAt,
        #[Groups(['attachment:read'])]
        public string $downloadUrl,
    ) {
        parent::__construct($id, $originFilename, $mimeType, $size, $status, $createdAt);
    }
}
