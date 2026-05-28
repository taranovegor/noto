<?php

namespace App\Tests\Unit\Component\Ai\Extractor;

use App\Component\Ai\Extractor\ExtractionResult;
use App\Component\Ai\StructuredOutput\StructuredOutputGenerator;
use PHPUnit\Framework\TestCase;

class ExtractionResultTest extends TestCase
{
    public function testExtractionResultCanBeGeneratedAsSchema(): void
    {
        $generator = new StructuredOutputGenerator();
        $schema = $generator->generate(ExtractionResult::class);

        $this->assertSame('json_schema', $schema['type']);
        $this->assertSame('extraction_result', $schema['name']);
        $this->assertTrue($schema['strict']);
        $this->assertSame('object', $schema['schema']['type']);
        $this->assertFalse($schema['schema']['additionalProperties']);
        $this->assertSame('string', $schema['schema']['properties']['result']['type']);
        $this->assertSame(['result'], $schema['schema']['required']);
    }

    public function testExtractionResultHasSchemaDescription(): void
    {
        $generator = new StructuredOutputGenerator();
        $schema = $generator->generate(ExtractionResult::class);

        $this->assertArrayHasKey('description', $schema['schema']['properties']['result']);
        $this->assertSame('The extracted text result', $schema['schema']['properties']['result']['description']);
    }

    public function testExtractionResultCanBeInstantiated(): void
    {
        $result = new ExtractionResult('test content');

        $this->assertSame('test content', $result->result);
    }

    public function testExtractionResultIsReadonly(): void
    {
        $result = new ExtractionResult('test');
        $reflection = new \ReflectionClass($result);

        $this->assertTrue($reflection->isReadonly());
    }
}
