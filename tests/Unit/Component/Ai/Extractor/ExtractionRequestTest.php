<?php

namespace App\Tests\Unit\Component\Ai\Extractor;

use App\Component\Ai\Extractor\Content\Text;
use App\Component\Ai\Extractor\ExtractionRequest;
use App\Component\Ai\Extractor\ExtractionResult;
use PHPUnit\Framework\TestCase;

class ExtractionRequestTest extends TestCase
{
    public function testExtractionRequestUsesExtractionResultByDefault(): void
    {
        $content = [new Text('test content')];
        $request = new ExtractionRequest(
            systemPrompt: 'Extract information',
            content: $content,
        );

        $this->assertSame(ExtractionResult::class, $request->schemaClass);
        $this->assertSame('Extract information', $request->systemPrompt);
        $this->assertSame($content, $request->content);
    }

    public function testExtractionRequestCanUseCustomSchemaClass(): void
    {
        $content = [new Text('test')];
        $request = new ExtractionRequest(
            systemPrompt: 'Extract',
            content: $content,
            schemaClass: 'CustomSchema',
        );

        $this->assertSame('CustomSchema', $request->schemaClass);
    }

    public function testExtractionRequestIsReadonly(): void
    {
        $request = new ExtractionRequest(
            systemPrompt: 'Extract',
            content: [],
        );
        $reflection = new \ReflectionClass($request);

        $this->assertTrue($reflection->isReadonly());
    }

    public function testExtractionRequestCanHaveMultipleContentBlocks(): void
    {
        $text = new Text('text content');
        $request = new ExtractionRequest(
            systemPrompt: 'Extract',
            content: [$text, $text],
        );

        $this->assertCount(2, $request->content);
    }
}
