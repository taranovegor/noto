<?php

namespace App\Dto\Notebook;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateNotebookDto
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        #[Assert\Length(max: 65535)]
        public ?string $description = null,
        #[Assert\Length(max: 65535)]
        public ?string $extractionInstructions = null,
    ) {
    }
}
