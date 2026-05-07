<?php

namespace App\Factory\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Dto\Attachment\AttachmentUploadResponseDto;
use App\Entity\Attachment;

readonly class AttachmentUploadResponseDtoFactory
{
    public function __construct(
        private ObjectStorage $storage,
    ) {
    }

    public function create(Attachment $attachment): AttachmentUploadResponseDto
    {
        $uploadUrl = $this->storage->uploadUrl($attachment->path, $attachment->mimeType, $attachment->size);

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
