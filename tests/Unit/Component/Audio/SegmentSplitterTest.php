<?php

namespace App\Tests\Unit\Component\Audio;

use App\Component\Audio\SegmentSplitter;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use PHPUnit\Framework\TestCase;

class SegmentSplitterTest extends TestCase
{
    public function testSegmentSplitterCanBeInstantiated(): void
    {
        $ffmpeg = $this->createStub(FFMpeg::class);
        $ffprobe = $this->createStub(FFProbe::class);

        $splitter = new SegmentSplitter($ffmpeg, $ffprobe);

        $this->assertNotNull($splitter);
    }

    public function testSegmentSplitterIsReadonly(): void
    {
        $ffmpeg = $this->createStub(FFMpeg::class);
        $ffprobe = $this->createStub(FFProbe::class);

        $splitter = new SegmentSplitter($ffmpeg, $ffprobe);
        $reflection = new \ReflectionClass($splitter);

        $this->assertTrue($reflection->isReadonly());
    }

    public function testSegmentSplitterWithCustomMaxChunkBytes(): void
    {
        $ffmpeg = $this->createStub(FFMpeg::class);
        $ffprobe = $this->createStub(FFProbe::class);
        $customMax = 10 * 1024 * 1024;

        $splitter = new SegmentSplitter($ffmpeg, $ffprobe, $customMax);

        $this->assertNotNull($splitter);
    }

    public function testSegmentSplitterWithDifferentChunkSizes(): void
    {
        $ffmpeg = $this->createStub(FFMpeg::class);
        $ffprobe = $this->createStub(FFProbe::class);

        $chunkSizes = [
            5 * 1024 * 1024,
            10 * 1024 * 1024,
            24 * 1024 * 1024,
            50 * 1024 * 1024,
        ];

        foreach ($chunkSizes as $size) {
            $splitter = new SegmentSplitter($ffmpeg, $ffprobe, $size);
            $this->assertNotNull($splitter);
        }
    }
}
