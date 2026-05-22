<?php

namespace App\Factory\Memo;

use App\Dto\Memo\MemoResponseDto;
use App\Entity\Attachment;
use App\Entity\Memo;
use App\Enum\LinkKind;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Service\Link\LinkResolver;

readonly class MemoResponseDtoFactory
{
    public function __construct(
        private LinkResolver $linkResolver,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
    ) {
    }

    public function create(Memo $memo): MemoResponseDto
    {
        $resolved = $this->linkResolver->resolve($memo->getRef(), LinkKind::Ownership, Attachment::class);
        $attachments = array_map($this->attachmentResponseDtoFactory->create(...), $resolved) ?: null;

        return new MemoResponseDto(
            $memo->id,
            $memo->content,
            $memo->createdAt,
            $memo->updatedAt,
            $attachments,
        );
    }
}
