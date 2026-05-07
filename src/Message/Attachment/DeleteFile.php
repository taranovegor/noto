<?php

namespace App\Message\Attachment;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteFile
{
    public function __construct(
        public string $path,
        public Uuid $id,
    ) {
    }
}
