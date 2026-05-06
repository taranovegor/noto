<?php

namespace App\Dto\Attachment;

use Symfony\Component\Serializer\Attribute\Groups;

readonly class AttachmentDownloadResponseDto
{
    public function __construct(
        #[Groups(['attachment:read'])]
        public string $downloadUrl,
    ) {
    }
}
