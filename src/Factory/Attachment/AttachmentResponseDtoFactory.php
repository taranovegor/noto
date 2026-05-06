<?php

namespace App\Factory\Attachment;

use App\Dto\Attachment\AttachmentResponseDto;
use App\Entity\Attachment;

class AttachmentResponseDtoFactory
{
    public function create(Attachment $attachment): AttachmentResponseDto
    {
        return new AttachmentResponseDto(
            $attachment->id,
            $attachment->originFilename,
            $attachment->mimeType,
            $attachment->size,
            $attachment->status,
            $attachment->createdAt,
        );
    }
}
