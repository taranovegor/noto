<?php

namespace App\Service\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Entity\Attachment;

final readonly class AttachmentDownloader
{
    public function __construct(
        private ObjectStorage $storage,
    ) {
    }

    public function download(Attachment $attachment): \SplFileInfo
    {
        return $this->storage->download($attachment->path, $attachment->originFilename);
    }
}
