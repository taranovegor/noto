<?php

namespace App\Dto\Note;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateNoteDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        #[Assert\Length(max: 65535)]
        public ?string $content = null,
    ) {
    }
}
