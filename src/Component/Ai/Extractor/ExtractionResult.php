<?php

namespace App\Component\Ai\Extractor;

use App\Component\Ai\StructuredOutput\Attribute\Schema;

final readonly class ExtractionResult
{
    public function __construct(
        #[Schema(description: 'The extracted text result')]
        public string $result,
    ) {
    }
}
