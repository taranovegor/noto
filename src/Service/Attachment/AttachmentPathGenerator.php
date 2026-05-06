<?php

namespace App\Service\Attachment;

use App\Entity\Attachment;

final readonly class AttachmentPathGenerator
{
    public function generate(Attachment $attachment): string
    {
        $ext = pathinfo($attachment->originFilename, PATHINFO_EXTENSION);

        return "attachments/{$attachment->id->toString()}".('' !== $ext ? ".{$ext}" : '');
    }
}
