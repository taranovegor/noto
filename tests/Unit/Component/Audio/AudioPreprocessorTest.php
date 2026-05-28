<?php

namespace App\Tests\Unit\Component\Audio;

use App\Component\Audio\AudioPreprocessor;
use App\Component\Audio\AudioProcessingHandler;
use PHPUnit\Framework\TestCase;

class AudioPreprocessorTest extends TestCase
{
    public function testPreprocessorReturnsChunksFromFirstHandler(): void
    {
        $inputFile = new \SplFileInfo(__FILE__);
        $chunk1 = new \SplFileInfo(__FILE__);
        $chunk2 = new \SplFileInfo(__FILE__);

        $handler1 = $this->createMock(AudioProcessingHandler::class);
        $handler1->expects($this->once())
            ->method('handle')
            ->with($inputFile)
            ->willReturn([$chunk1, $chunk2]);

        $preprocessor = new AudioPreprocessor([$handler1]);
        $result = $preprocessor->process($inputFile);

        $this->assertSame([$chunk1, $chunk2], $result);
    }

    public function testPreprocessorSkipsHandlerWithNoChunks(): void
    {
        $inputFile = new \SplFileInfo(__FILE__);
        $chunk = new \SplFileInfo(__FILE__);

        $handler1 = $this->createMock(AudioProcessingHandler::class);
        $handler1->expects($this->once())
            ->method('handle')
            ->willReturn([]);

        $handler2 = $this->createMock(AudioProcessingHandler::class);
        $handler2->expects($this->once())
            ->method('handle')
            ->willReturn([$chunk]);

        $preprocessor = new AudioPreprocessor([$handler1, $handler2]);
        $result = $preprocessor->process($inputFile);

        $this->assertSame([$chunk], $result);
    }

    public function testPreprocessorContinuesToNextHandlerIfSingleChunkWithDifferentPath(): void
    {
        $inputFile = $this->createStub(\SplFileInfo::class);
        $inputFile->method('getPathname')->willReturn('/original/path');

        $processedFile = $this->createStub(\SplFileInfo::class);
        $processedFile->method('getPathname')->willReturn('/processed/path');

        $finalChunks = [
            $this->createStub(\SplFileInfo::class),
            $this->createStub(\SplFileInfo::class),
        ];

        $handler1 = $this->createMock(AudioProcessingHandler::class);
        $handler1->expects($this->once())
            ->method('handle')
            ->with($inputFile)
            ->willReturn([$processedFile]);

        $handler2 = $this->createMock(AudioProcessingHandler::class);
        $handler2->expects($this->once())
            ->method('handle')
            ->with($processedFile)
            ->willReturn($finalChunks);

        $preprocessor = new AudioPreprocessor([$handler1, $handler2]);
        $result = $preprocessor->process($inputFile);

        $this->assertCount(2, $result);
    }

    public function testPreprocessorThrowsWhenNoHandlerProducesResult(): void
    {
        $inputFile = new \SplFileInfo(__FILE__);

        $handler = $this->createStub(AudioProcessingHandler::class);
        $handler->method('handle')->willReturn([]);

        $preprocessor = new AudioPreprocessor([$handler]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Audio processing chain exhausted without result');

        $preprocessor->process($inputFile);
    }

    public function testPreprocessorWithNoHandlers(): void
    {
        $inputFile = new \SplFileInfo(__FILE__);
        $preprocessor = new AudioPreprocessor([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Audio processing chain exhausted without result');

        $preprocessor->process($inputFile);
    }
}
