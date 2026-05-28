<?php

namespace App\Service\Extraction\Processor;

use App\Component\Audio\AudioPreprocessor;
use App\Component\Storage\ObjectStorage;
use App\Dto\Extraction\Plan\PlannedFragment;
use App\Dto\Extraction\Plan\SourceRef;
use App\Entity\Attachment;
use App\Enum\Extraction\FragmentType;
use App\Service\Attachment\AttachmentDownloader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Uid\Uuid;

/**
 * Splits audio into transcribable segments. Each segment is uploaded to object
 * storage under a temporary key and referenced by that key — the transcription
 * worker pulls it from storage, so nothing crosses the worker boundary on the
 * local filesystem. Local temp files (download + segments) are cleaned up here.
 */
#[AutoconfigureTag('app.extraction.attachment_processor', ['priority' => 30])]
final readonly class AudioAttachmentProcessor implements AttachmentProcessor
{
    public function __construct(
        private AttachmentDownloader $downloader,
        private AudioPreprocessor $audioPreprocessor,
        private ObjectStorage $tempStorage,
    ) {
    }

    public function supports(Attachment $attachment): bool
    {
        return str_starts_with($attachment->mimeType, 'audio/');
    }

    public function plan(Attachment $attachment): iterable
    {
        $file = $this->downloader->download($attachment);

        $localPaths = [$file->getPathname()];

        try {
            try {
                $segments = $this->audioPreprocessor->process($file);
            } catch (\RuntimeException) {
                yield $this->store($file->getPathname(), $attachment);

                return;
            }

            foreach ($segments as $segment) {
                $path = $segment->getPathname();

                if (!in_array($path, $localPaths, true)) {
                    $localPaths[] = $path;
                }

                yield $this->store($path, $attachment);
            }
        } finally {
            foreach ($localPaths as $path) {
                @unlink($path);
            }
        }
    }

    private function store(string $path, Attachment $attachment): PlannedFragment
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $suffix = '' !== $extension ? '.'.$extension : '';
        $key = sprintf('extraction/%s%s', Uuid::v7()->toRfc4122(), $suffix);

        $mimeType = mime_content_type($path) ?: $attachment->mimeType;
        $filename = pathinfo($attachment->originFilename, PATHINFO_FILENAME).('.'.$extension);

        $this->tempStorage->upload($key, new \SplFileInfo($path));

        return PlannedFragment::of(
            FragmentType::AudioTranscript,
            new SourceRef($key, $mimeType, $filename),
        );
    }
}
