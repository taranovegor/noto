<?php

namespace App\Dto\Note;

use App\Entity\Attachment;
use App\Validator\RefNotOwned;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateNoteDto
{
    /**
     * @param Attachment[]|null $attachments
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\Length(max: 65535)]
        public string $content,
        #[Assert\All([new RefNotOwned()])]
        public ?array $attachments = null,
    ) {
    }
}
