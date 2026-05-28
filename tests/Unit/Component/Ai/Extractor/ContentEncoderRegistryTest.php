<?php

namespace App\Tests\Unit\Component\Ai\Extractor;

use App\Component\Ai\Extractor\Content\ContentEncoder;
use App\Component\Ai\Extractor\Content\Text;
use App\Component\Ai\Extractor\ContentEncoderRegistry;
use PHPUnit\Framework\TestCase;

class ContentEncoderRegistryTest extends TestCase
{
    public function testRegistryEncodesWithFirstSupportingEncoder(): void
    {
        $text = new Text('hello');

        $encoder1 = $this->createMock(ContentEncoder::class);
        $encoder1->expects($this->once())
            ->method('supports')
            ->with($text)
            ->willReturn(false);

        $encoder2 = $this->createMock(ContentEncoder::class);
        $encoder2->expects($this->once())
            ->method('supports')
            ->with($text)
            ->willReturn(true);
        $encoder2->expects($this->once())
            ->method('encode')
            ->with($text)
            ->willReturn(['type' => 'text', 'value' => 'hello']);

        $registry = new ContentEncoderRegistry([$encoder1, $encoder2]);
        $result = $registry->encode($text);

        $this->assertSame(['type' => 'text', 'value' => 'hello'], $result);
    }

    public function testRegistryThrowsWhenNoEncoderSupportsBlock(): void
    {
        $block = new \stdClass();

        $encoder = $this->createStub(ContentEncoder::class);
        $encoder->method('supports')->willReturn(false);

        $registry = new ContentEncoderRegistry([$encoder]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported content block type');

        $registry->encode($block);
    }

    public function testRegistryWithMultipleEncoders(): void
    {
        $text = new Text('test');

        $encoder1 = $this->createStub(ContentEncoder::class);
        $encoder1->method('supports')->willReturn(false);

        $encoder2 = $this->createStub(ContentEncoder::class);
        $encoder2->method('supports')->willReturn(false);

        $encoder3 = $this->createStub(ContentEncoder::class);
        $encoder3->method('supports')->willReturn(true);
        $encoder3->method('encode')->willReturn(['encoded' => true]);

        $registry = new ContentEncoderRegistry([$encoder1, $encoder2, $encoder3]);
        $result = $registry->encode($text);

        $this->assertSame(['encoded' => true], $result);
    }

    public function testRegistryWithEmptyEncoders(): void
    {
        $block = new \stdClass();
        $registry = new ContentEncoderRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported content block type');

        $registry->encode($block);
    }
}
