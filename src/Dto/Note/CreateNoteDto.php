<?php

namespace App\Dto\Note;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateNoteDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\Length(max: 65535)]
        public string $content,
    ) {
    }
}
