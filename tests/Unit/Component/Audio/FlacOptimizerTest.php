<?php

namespace App\Tests\Unit\Component\Audio;

use App\Component\Audio\FlacOptimizer;
use FFMpeg\Driver\FFMpegDriver;
use FFMpeg\FFMpeg;
use PHPUnit\Framework\TestCase;

class FlacOptimizerTest extends TestCase
{
    private FlacOptimizer $optimizer;
    private FFMpeg $ffmpeg;
    private FFMpegDriver $driver;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(FFMpegDriver::class);
        $this->ffmpeg = $this->createStub(FFMpeg::class);
        $this->ffmpeg->method('getFFMpegDriver')->willReturn($this->driver);

        $this->optimizer = new FlacOptimizer($this->ffmpeg);
    }

    public function testOptimizerSupportsAudioFile(): void
    {
        $file = new \SplFileInfo(__FILE__);

        $this->driver->expects($this->once())
            ->method('command')
            ->willReturn('');

        $result = iterator_to_array($this->optimizer->handle($file));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(\SplFileInfo::class, $result[0]);
    }

    public function testOptimizerCommandIncludesCorrectFormat(): void
    {
        $file = new \SplFileInfo(__FILE__);

        $this->driver->expects($this->once())
            ->method('command')
            ->with($this->callback(function (array $command): bool {
                return in_array('flac', $command, true)
                       && in_array('16000', $command, true)
                       && in_array('1', $command, true);
            }))
            ->willReturn('');

        iterator_to_array($this->optimizer->handle($file));
    }

    public function testOptimizerThrowsOnFFMpegFailure(): void
    {
        $file = new \SplFileInfo(__FILE__);

        $this->driver->expects($this->once())
            ->method('command')
            ->willThrowException(new \Exception('FFMpeg error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ffmpeg FLAC optimization failed');

        iterator_to_array($this->optimizer->handle($file));
    }
}
