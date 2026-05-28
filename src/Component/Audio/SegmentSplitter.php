<?php

namespace App\Component\Audio;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.audio.processing_handler', ['priority' => 7])]
final readonly class SegmentSplitter implements AudioProcessingHandler
{
    public function __construct(
        private FFMpeg $ffmpeg,
        private FFProbe $ffprobe,
        private int $maxChunkBytes = 24 * 1024 * 1024,
    ) {
    }

    public function handle(\SplFileInfo $file): iterable
    {
        $fileSize = filesize($file->getPathname());

        if (false !== $fileSize && $fileSize < $this->maxChunkBytes) {
            return [$file];
        }

        $duration = $this->ffprobe
            ->format($file->getPathname())
            ->get('duration');

        if (!is_numeric($duration)) {
            throw new \RuntimeException(sprintf('Unable to determine duration: %s', $file->getPathname()));
        }

        $duration = (float) $duration;
        $chunkDuration = (int) ceil($duration * ($this->maxChunkBytes / $fileSize));

        $ext = $file->getExtension();
        if ('' === $ext) {
            throw new \RuntimeException(sprintf('Audio file has no extension: %s', $file->getPathname()));
        }

        $outputDir = sys_get_temp_dir().'/segment_splitter_'.bin2hex(random_bytes(8));
        mkdir($outputDir, 0755);

        $outputPattern = $outputDir.'/chunk_%03d.'.$ext;

        try {
            $this->ffmpeg->getFFMpegDriver()->command([
                '-y',
                '-i', $file->getPathname(),
                '-f', 'segment',
                '-segment_time', (string) $chunkDuration,
                '-c:a', 'flac',
                $outputPattern,
            ]);
        } catch (\Throwable $e) {
            $this->cleanupDir($outputDir);

            throw new \RuntimeException('ffmpeg segmentation failed: '.$e->getMessage(), previous: $e);
        }

        $files = glob($outputDir.'/chunk_*');

        if (!$files) {
            $this->cleanupDir($outputDir);

            throw new \RuntimeException('ffmpeg produced no chunk files.');
        }

        sort($files, SORT_NATURAL);

        foreach ($files as $fileItem) {
            yield new \SplFileInfo($fileItem);
        }
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
