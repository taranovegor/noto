<?php

namespace App\Factory\Attachment;

use App\Dto\Attachment\AttachmentUploadResponseDto;
use App\Entity\Attachment;
use App\Service\Attachment\AttachmentUrlGenerator;

readonly class AttachmentUploadResponseDtoFactory
{
    public function __construct(
        private AttachmentUrlGenerator $urlGenerator,
    ) {
    }

    public function create(Attachment $attachment): AttachmentUploadResponseDto
    {
        $uploadUrl = $this->urlGenerator->generateUploadUrl($attachment);

        return new AttachmentUploadResponseDto(
            $attachment->id,
            $attachment->originFilename,
            $attachment->mimeType,
            $attachment->size,
            $attachment->status,
            $attachment->createdAt,
            $uploadUrl,
        );
    }
}
