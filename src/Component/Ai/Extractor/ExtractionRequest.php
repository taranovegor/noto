<?php

namespace App\Component\Ai\Extractor;

use App\Component\Ai\Extractor\Content\File;
use App\Component\Ai\Extractor\Content\Image;
use App\Component\Ai\Extractor\Content\Text;

/**
 * @template T of object
 */
final readonly class ExtractionRequest
{
    /**
     * @param list<Text|Image|File> $content
     * @param class-string          $schemaClass
     */
    public function __construct(
        public string $systemPrompt,
        public array $content,
        public string $schemaClass = ExtractionResult::class,
    ) {
    }
}
