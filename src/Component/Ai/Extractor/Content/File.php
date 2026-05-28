<?php

namespace App\Component\Ai\Extractor\Content;

/**
 * A document (pdf, office, spreadsheet, text) passed to the model by URL. The
 * provider fetches it directly, so the URL must be reachable from its network
 * (a presigned storage URL). The filename gives the model the format hint.
 */
final readonly class File
{
    public function __construct(
        public string $url,
        public string $filename,
        public string $mimeType,
    ) {
    }
}
