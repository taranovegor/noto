<?php

namespace App\Tests\Unit\Component\Ai\Store\Document;

use App\Component\Ai\Store\Document\PlainTextTransformer;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;

class PlainTextTransformerTest extends TestCase
{
    private function makeConverter(string $html): ConverterInterface
    {
        $rendered = $this->createStub(RenderedContentInterface::class);
        $rendered->method('__toString')->willReturn($html);

        $converter = $this->createStub(ConverterInterface::class);
        $converter->method('convert')->willReturn($rendered);

        return $converter;
    }

    public function testTransformStripsHtmlTags(): void
    {
        $transformer = new PlainTextTransformer($this->makeConverter('<p><strong>bold</strong> text</p>'));
        $document = new TextDocument('id', '**bold** text', new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertSame('bold text', $result[0]->getContent());
    }

    public function testTransformDecodesHtmlEntities(): void
    {
        $transformer = new PlainTextTransformer($this->makeConverter('<p>hello &amp; world</p>'));
        $document = new TextDocument('id', 'hello & world', new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertSame('hello & world', $result[0]->getContent());
    }

    public function testTransformPreservesDocumentId(): void
    {
        $transformer = new PlainTextTransformer($this->makeConverter('<p>text</p>'));
        $document = new TextDocument('my-id', 'text', new Metadata([]));

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertSame('my-id', $result[0]->getId());
    }

    public function testTransformPreservesMetadata(): void
    {
        $transformer = new PlainTextTransformer($this->makeConverter('<p>text</p>'));
        $metadata = new Metadata([Metadata::KEY_PARENT_ID => 'parent-123']);
        $document = new TextDocument('id', 'text', $metadata);

        $result = iterator_to_array($transformer->transform([$document]));

        $this->assertSame('parent-123', $result[0]->getMetadata()[Metadata::KEY_PARENT_ID]);
    }

    public function testTransformProcessesMultipleDocuments(): void
    {
        $transformer = new PlainTextTransformer($this->makeConverter('<p>text</p>'));
        $documents = [
            new TextDocument('id1', 'a', new Metadata([])),
            new TextDocument('id2', 'b', new Metadata([])),
        ];

        $result = iterator_to_array($transformer->transform($documents));

        $this->assertCount(2, $result);
    }
}
