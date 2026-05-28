<?php

namespace App\Message\Extraction;

use Symfony\Component\Uid\Uuid;

final readonly class MergeExtractionResults
{
    public function __construct(
        public Uuid $extractionId,
    ) {
    }
}
