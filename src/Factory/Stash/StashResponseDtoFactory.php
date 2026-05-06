<?php

namespace App\Factory\Stash;

use App\Dto\Stash\StashResponseDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Attachment\AttachmentUploadResponseDtoFactory;
use App\Service\Attachment\AttachmentManager;

readonly class StashResponseDtoFactory
{
    public function __construct(
        private AttachmentResponseDtoFactory $responseDtoFactory,
        private AttachmentUploadResponseDtoFactory $uploadResponseDtoFactory,
        private AttachmentManager $attachmentManager,
    ) {
    }

    public function create(Stash $stash): StashResponseDto
    {
        $attachments = array_map(
            fn (Attachment $a) => $this->responseDtoFactory->create($a),
            $this->attachmentManager->getOwnedBy($stash->ref),
        );

        return new StashResponseDto(
            $stash->id,
            $stash->type,
            $stash->content,
            $stash->createdAt,
            $stash->expiresAt,
            $stash->pinned,
            [] !== $attachments ? $attachments : null,
        );
    }

    public function createWithUploadUrls(Stash $stash): StashResponseDto
    {
        $attachments = array_map(
            fn (Attachment $a) => $this->uploadResponseDtoFactory->create($a),
            $this->attachmentManager->getOwnedBy($stash->ref),
        );

        return new StashResponseDto(
            $stash->id,
            $stash->type,
            $stash->content,
            $stash->createdAt,
            $stash->expiresAt,
            $stash->pinned,
            [] !== $attachments ? $attachments : null,
        );
    }
}
