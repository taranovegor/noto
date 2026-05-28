<?php

namespace App\Service\Extraction\Fragment;

use App\Component\Ai\Extractor\Content\File;
use App\Component\Ai\Extractor\Content\Image;
use App\Component\Ai\Extractor\Content\Text;
use App\Component\Storage\ObjectStorage;
use App\Entity\Extraction;
use App\Enum\Extraction\FragmentStatus;
use App\Enum\Extraction\FragmentType;

/**
 * Turns an extraction's completed fragments into model input blocks: audio
 * transcripts become text (numbered when several), images and documents are
 * referenced by a freshly minted presigned URL. Failed/pending fragments are
 * skipped; returns an empty list when nothing is usable.
 */
final readonly class FragmentContentAssembler
{
    public function __construct(
        private ObjectStorage $storage,
    ) {
    }

    /**
     * @return list<Text|Image|File>
     */
    public function assemble(Extraction $extraction): array
    {
        $transcripts = [];
        $images = [];
        $documents = [];

        foreach ($extraction->getFragments() as $fragment) {
            if (FragmentStatus::Done !== $fragment->status) {
                continue;
            }

            match ($fragment->type) {
                FragmentType::AudioTranscript => $transcripts[] = $fragment->result ?? '',
                FragmentType::Image => $images[] = $fragment,
                FragmentType::Document => $documents[] = $fragment,
            };
        }

        $content = [];

        if ($transcripts) {
            $content[] = new Text($this->joinTranscripts($transcripts));
        }

        // Images and documents go to the model by URL; the presigned URL is
        // minted here, moments before the call, so its short TTL is plenty.
        foreach ($images as $fragment) {
            $content[] = new Image($this->storage->downloadUrl($fragment->storageKey ?? '', $fragment->filename ?? 'image'));
        }

        foreach ($documents as $fragment) {
            $content[] = new File(
                $this->storage->downloadUrl($fragment->storageKey ?? '', $fragment->filename ?? 'document'),
                $fragment->filename ?? 'document',
                $fragment->mimeType ?? 'application/octet-stream',
            );
        }

        return $content;
    }

    /**
     * @param list<string> $transcripts
     */
    private function joinTranscripts(array $transcripts): string
    {
        if (1 === count($transcripts)) {
            return $transcripts[0];
        }

        $total = count($transcripts);
        $parts = [];
        foreach ($transcripts as $i => $text) {
            $parts[] = sprintf("[Part %d/%d]\n%s", $i + 1, $total, $text);
        }

        return implode("\n\n", $parts);
    }
}
