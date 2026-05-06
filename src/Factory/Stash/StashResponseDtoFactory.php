<?php

namespace App\Factory\Stash;

use App\Dto\Stash\StashResponseDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Enum\LinkKind;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Attachment\AttachmentUploadResponseDtoFactory;
use App\Service\Link\LinkResolver;

readonly class StashResponseDtoFactory
{
    public function __construct(
        private AttachmentResponseDtoFactory $responseDtoFactory,
        private AttachmentUploadResponseDtoFactory $uploadResponseDtoFactory,
        private LinkResolver $linkResolver,
    ) {
    }

    public function create(Stash $stash): StashResponseDto
    {
        $attachments = array_map(
            fn (Attachment $a) => $this->responseDtoFactory->create($a),
            $this->linkResolver->resolve($stash, LinkKind::Ownership, Attachment::class),
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
            $this->linkResolver->resolve($stash, LinkKind::Ownership, Attachment::class),
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
