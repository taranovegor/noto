<?php

namespace App\Dto\Extraction\Plan;

/**
 * A reference to attachment-derived bytes living in object storage. Carries the
 * stable storage key (never a presigned URL — those expire); the URL is minted
 * just before it is handed to the model or an async worker.
 */
final readonly class SourceRef
{
    public function __construct(
        public string $storageKey,
        public string $mimeType,
        public string $filename,
    ) {
    }
}
