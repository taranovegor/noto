<?php

namespace App\Dto\Note;

use App\Entity\Attachment;
use App\Validator\RefNotOwned;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AttachNoteAttachmentsDto
{
    /**
     * @param Attachment[] $attachments
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([new RefNotOwned()])]
        public array $attachments,
    ) {
    }
}
