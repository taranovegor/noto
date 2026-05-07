<?php

namespace App\Factory\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Dto\Attachment\AttachmentDownloadResponseDto;
use App\Entity\Attachment;

readonly class AttachmentDownloadResponseDtoFactory
{
    public function __construct(
        private ObjectStorage $storage,
    ) {
    }

    public function create(Attachment $attachment): AttachmentDownloadResponseDto
    {
        $downloadUrl = $this->storage->downloadUrl($attachment->path, $attachment->originFilename);

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
