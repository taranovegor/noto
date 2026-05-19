<?php

namespace App\Factory\Stash;

use App\Dto\Stash\StashResponseDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Enum\LinkKind;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Service\Link\LinkResolver;

readonly class StashResponseDtoFactory
{
    public function __construct(
        private AttachmentResponseDtoFactory $responseDtoFactory,
        private LinkResolver $linkResolver,
    ) {
    }

    public function create(Stash $stash): StashResponseDto
    {
        $attachments = array_map(
            fn (Attachment $a) => $this->responseDtoFactory->create($a),
            $this->linkResolver->resolve($stash->getRef(), LinkKind::Ownership, Attachment::class),
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
