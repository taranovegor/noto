<?php

namespace App\Component\Audio;

use FFMpeg\FFMpeg;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.audio.processing_handler', ['priority' => 9])]
final readonly class FlacOptimizer implements AudioProcessingHandler
{
    public function __construct(
        private FFMpeg $ffmpeg,
    ) {
    }

    public function handle(\SplFileInfo $file): iterable
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'flac_optimizer_').'.flac';

        try {
            $this->ffmpeg->getFFMpegDriver()->command([
                '-y',
                '-i', $file->getPathname(),
                '-ar', '16000',
                '-ac', '1',
                '-map', '0:a',
                '-c:a', 'flac',
                $outputFile,
            ]);
        } catch (\Throwable $e) {
            @unlink($outputFile);

            throw new \RuntimeException('ffmpeg FLAC optimization failed: '.$e->getMessage(), previous: $e);
        }

        yield new \SplFileInfo($outputFile);
    }
}
