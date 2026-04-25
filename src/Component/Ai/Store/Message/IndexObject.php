<?php

namespace App\Component\Ai\Store\Message;

use App\Component\Ai\Store\Document\IndexableReference;

readonly class IndexObject
{
    public function __construct(
        public IndexableReference $reference,
    ) {
    }
}
