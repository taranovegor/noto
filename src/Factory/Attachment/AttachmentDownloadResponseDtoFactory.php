<?php

namespace App\Factory\Attachment;

use App\Dto\Attachment\AttachmentDownloadResponseDto;
use App\Entity\Attachment;
use App\Service\Attachment\AttachmentUrlGenerator;

readonly class AttachmentDownloadResponseDtoFactory
{
    public function __construct(
        private AttachmentUrlGenerator $urlGenerator,
    ) {
    }

    public function create(Attachment $attachment): AttachmentDownloadResponseDto
    {
        $downloadUrl = $this->urlGenerator->generateDownloadUrl($attachment);

        return new AttachmentDownloadResponseDto(
            $attachment->id,
            $attachment->originFilename,
            $attachment->mimeType,
            $attachment->size,
            $attachment->status,
            $attachment->createdAt,
            $downloadUrl,
        );
    }
}
