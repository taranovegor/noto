<?php

namespace App\Factory\Note;

use App\Dto\Note\NoteResponseDto;
use App\Entity\Attachment;
use App\Entity\Note;
use App\Enum\LinkKind;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Service\Link\LinkResolver;

class NoteResponseDtoFactory
{
    public function __construct(
        private readonly LinkResolver $linkResolver,
        private readonly AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
    ) {
    }

    public function create(Note $note): NoteResponseDto
    {
        $resolved = $this->linkResolver->resolve($note, LinkKind::Ownership, Attachment::class);
        $attachments = array_map($this->attachmentResponseDtoFactory->create(...), $resolved) ?: null;

        return new NoteResponseDto(
            $note->id,
            $note->content,
            $note->createdAt,
            $note->updatedAt,
            $attachments,
        );
    }
}
