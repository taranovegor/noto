<?php

namespace App\Service\Extraction\Processor;

use App\Dto\Extraction\Plan\PlannedFragment;
use App\Dto\Extraction\Plan\SourceRef;
use App\Entity\Attachment;
use App\Enum\Extraction\FragmentType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Plans an image as a reference fragment. The multimodal model receives the
 * original image by URL in the final extraction call — no separate description
 * step, so the model sees the real pixels instead of a lossy paraphrase.
 */
#[AutoconfigureTag('app.extraction.attachment_processor', ['priority' => 20])]
final readonly class ImageAttachmentProcessor implements AttachmentProcessor
{
    public function supports(Attachment $attachment): bool
    {
        return str_starts_with($attachment->mimeType, 'image/');
    }

    public function plan(Attachment $attachment): iterable
    {
        yield PlannedFragment::of(
            FragmentType::Image,
            new SourceRef($attachment->path, $attachment->mimeType, $attachment->originFilename),
        );
    }
}
