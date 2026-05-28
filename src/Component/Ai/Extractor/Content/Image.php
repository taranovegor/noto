<?php

namespace App\Component\Ai\Extractor\Content;

/**
 * An image passed to a multimodal model by URL. The model fetches it directly,
 * so the URL must be reachable from the provider (a presigned storage URL).
 */
final readonly class Image
{
    public function __construct(
        public string $url,
    ) {
    }
}
