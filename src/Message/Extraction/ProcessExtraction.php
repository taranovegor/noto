<?php

namespace App\Message\Extraction;

use Symfony\Component\Uid\Uuid;

final readonly class ProcessExtraction
{
    public function __construct(
        public Uuid $extractionId,
    ) {
    }
}
