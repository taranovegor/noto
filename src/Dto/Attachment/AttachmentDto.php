<?php

namespace App\Dto\Attachment;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AttachmentDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $originFilename,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $mimeType,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $size,
    ) {
    }
}
