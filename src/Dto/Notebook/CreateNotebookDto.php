<?php

namespace App\Dto\Notebook;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateNotebookDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\Length(max: 65535)]
        public string $description,
        #[Assert\Length(max: 65535)]
        public ?string $extractionInstructions = null,
    ) {
    }
}
