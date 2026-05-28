<?php

namespace App\Message\Extraction;

use Symfony\Component\Uid\Uuid;

final readonly class ProcessAudioChunk
{
    public function __construct(
        public Uuid $extractionId,
        public string $fragmentId,
        public string $storageKey,
        public string $filename,
    ) {
    }
}
