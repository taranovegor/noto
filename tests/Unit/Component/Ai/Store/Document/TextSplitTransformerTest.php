<?php

namespace App\Tests\Unit\Component\Ai\Store\Document;

use App\Component\Ai\Store\Document\TextSplitTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;

class TextSplitTransformerTest extends TestCase
{
    public function testConstructorThrowsWhenOverlapEqualsChunkSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextSplitTransformer(100, 100);
    }

    public function testConstructorThrowsWhenOverlapExceedsChunkSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextSplitTransformer(100, 101);
    }

    public function testConstructorThrowsWhenOverlapIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextSplitTransformer(100, -1);
    }

    public function testShortDocumentReturnedAsIsSingleChunk(): void
    {
        $transformer = new TextSplitTransformer(100, 0);
        $document = new TextDocument('test-id', 'short content', new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertCount(1, $result);
        $this->assertSame('test-id', $result[0]->getId());
        $this->assertSame('short content', $result[0]->getContent());
    }

    public function testDocumentExactlyChunkSizeReturnedAsIs(): void
    {
        $transformer = new TextSplitTransformer(5, 0);
        $document = new TextDocument('id', 'hello', new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertCount(1, $result);
        $this->assertSame('hello', $result[0]->getContent());
    }

    public function testLongDocumentIsSplitIntoChunks(): void
    {
        $transformer = new TextSplitTransformer(10, 0);
        $content = str_repeat('a', 25);
        $document = new TextDocument('id', $content, new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertCount(3, $result);
        $this->assertSame(str_repeat('a', 10), $result[0]->getContent());
        $this->assertSame(str_repeat('a', 10), $result[1]->getContent());
        $this->assertSame(str_repeat('a', 5), $result[2]->getContent());
    }

    public function testOverlapProducesOverlappingChunks(): void
    {
        $transformer = new TextSplitTransformer(10, 5);
        $content = 'abcdefghijklmnopqrst'; // 20 chars
        $document = new TextDocument('id', $content, new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertSame('abcdefghij', $result[0]->getContent());
        $this->assertSame('fghijklmno', $result[1]->getContent());
        $this->assertSame('klmnopqrst', $result[2]->getContent());
    }

    public function testOptionsOverrideConstructorDefaults(): void
    {
        $transformer = new TextSplitTransformer(1000, 200);
        $document = new TextDocument('id', str_repeat('a', 25), new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document], [
            TextSplitTransformer::OPTION_CHUNK_SIZE => 10,
            TextSplitTransformer::OPTION_OVERLAP => 0,
        ]));

        $this->assertCount(3, $result);
    }

    public function testSplitChunksGetNewIds(): void
    {
        $transformer = new TextSplitTransformer(10, 0);
        $document = new TextDocument('original-id', str_repeat('a', 25), new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        foreach ($result as $chunk) {
            $this->assertNotSame('original-id', $chunk->getId());
        }
    }

    public function testMetadataIsPreservedAcrossChunks(): void
    {
        $transformer = new TextSplitTransformer(10, 0);
        $metadata = new Metadata([Metadata::KEY_PARENT_ID => 'parent-123']);
        $document = new TextDocument('id', str_repeat('a', 25), $metadata);

        $result = iterator_to_array($transformer->transform([$document]));

        foreach ($result as $chunk) {
            $this->assertSame('parent-123', $chunk->getMetadata()[Metadata::KEY_PARENT_ID]);
        }
    }
}
