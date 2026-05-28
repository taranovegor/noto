<?php

namespace App\Dto\Note;

use App\Component\Ai\StructuredOutput\Attribute\Schema;

final readonly class NoteExtractedContent
{
    public function __construct(
        #[Schema(description: 'Concise content-derived title, under 80 characters')]
        public string $title,
        #[Schema(description: 'Well-structured Markdown body of the note')]
        public string $content,
    ) {
    }
}
