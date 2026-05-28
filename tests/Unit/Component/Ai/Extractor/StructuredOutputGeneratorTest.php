<?php

namespace App\Tests\Unit\Component\Ai\Extractor;

use App\Component\Ai\StructuredOutput\Attribute\Schema;
use App\Component\Ai\StructuredOutput\Exception\UnsupportedSchemaTypeException;
use App\Component\Ai\StructuredOutput\StructuredOutputGenerator;
use PHPUnit\Framework\TestCase;

class StructuredOutputGeneratorTest extends TestCase
{
    private StructuredOutputGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new StructuredOutputGenerator();
    }

    public function testGenerateWithStringProperties(): void
    {
        $schema = $this->generator->generate(FlatStringDto::class);

        $this->assertSame('json_schema', $schema['type']);
        $this->assertSame('flat_string_dto', $schema['name']);
        $this->assertTrue($schema['strict']);
        $this->assertSame('object', $schema['schema']['type']);
        $this->assertFalse($schema['schema']['additionalProperties']);
        $this->assertSame(['type' => 'string'], $schema['schema']['properties']['title']);
        $this->assertSame(['type' => 'string'], $schema['schema']['properties']['content']);
        $this->assertSame(['title', 'content'], $schema['schema']['required']);
    }

    public function testGenerateWithMixedScalars(): void
    {
        $schema = $this->generator->generate(MixedScalarsDto::class);

        $this->assertSame(['type' => 'integer'], $schema['schema']['properties']['count']);
        $this->assertSame(['type' => 'number'], $schema['schema']['properties']['score']);
        $this->assertSame(['type' => 'boolean'], $schema['schema']['properties']['enabled']);
        $this->assertSame(['type' => 'string'], $schema['schema']['properties']['label']);
    }

    public function testNullableProperty(): void
    {
        $schema = $this->generator->generate(NullableDto::class);

        $this->assertSame(['type' => ['string', 'null']], $schema['schema']['properties']['title']);
        $this->assertSame(['type' => ['integer', 'null']], $schema['schema']['properties']['count']);
        $this->assertContains('title', $schema['schema']['required']);
    }

    public function testBackedStringEnum(): void
    {
        $schema = $this->generator->generate(StringEnumDto::class);

        $prop = $schema['schema']['properties']['status'];
        $this->assertSame('string', $prop['type']);
        $this->assertSame(['todo', 'done'], $prop['enum']);
    }

    public function testBackedIntEnum(): void
    {
        $schema = $this->generator->generate(IntEnumDto::class);

        $prop = $schema['schema']['properties']['priority'];
        $this->assertSame('integer', $prop['type']);
        $this->assertSame([1, 2, 3], $prop['enum']);
    }

    public function testNestedObject(): void
    {
        $schema = $this->generator->generate(NestedWrapperDto::class);

        $inner = $schema['schema']['properties']['inner'];
        $this->assertSame('object', $inner['type']);
        $this->assertFalse($inner['additionalProperties']);
        $this->assertSame(['type' => 'string'], $inner['properties']['name']);
        $this->assertSame(['name'], $inner['required']);
    }

    public function testDescriptionFromAttribute(): void
    {
        $schema = $this->generator->generate(DescribedDto::class);

        $prop = $schema['schema']['properties']['title'];
        $this->assertSame('Short title, up to 80 chars', $prop['description']);
        $this->assertSame('string', $prop['type']);
    }

    public function testNullableEnum(): void
    {
        $schema = $this->generator->generate(NullableEnumDto::class);

        $prop = $schema['schema']['properties']['status'];
        $this->assertSame(['string', 'null'], $prop['type']);
        $this->assertSame(['pending', 'active'], $prop['enum']);
    }

    public function testNullableNestedObject(): void
    {
        $schema = $this->generator->generate(NullableNestedDto::class);

        $prop = $schema['schema']['properties']['inner'];
        $this->assertSame(['object', 'null'], $prop['type']);
    }

    public function testSnakeCaseName(): void
    {
        $schema = $this->generator->generate(CamelCaseDtoName::class);

        $this->assertSame('camel_case_dto_name', $schema['name']);
    }

    public function testResultCache(): void
    {
        $a = $this->generator->generate(FlatStringDto::class);
        $b = $this->generator->generate(FlatStringDto::class);

        $this->assertSame($a, $b);
    }

    // ---- negative tests ----

    public function testNoConstructorThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('must have a constructor');

        $this->generator->generate(NoConstructorDto::class);
    }

    public function testNoTypeDeclarationThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('no type declaration');

        $this->generator->generate(UntypedPropertyDto::class);
    }

    public function testUnionTypeThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('union');

        $this->generator->generate(UnionDto::class);
    }

    public function testIntersectionTypeThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('intersection');

        $this->generator->generate(IntersectionDto::class);
    }

    public function testNonBackedEnumThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('non-backed enum');

        $this->generator->generate(NonBackedEnumDto::class);
    }

    public function testArrayThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('unsupported type');

        $this->generator->generate(BareArrayDto::class);
    }

    public function testCircularReferenceThrows(): void
    {
        $this->expectException(UnsupportedSchemaTypeException::class);
        $this->expectExceptionMessage('Circular reference');

        $this->generator->generate(CircularA::class);
    }
}

// ---- DTOs ----

final readonly class FlatStringDto
{
    public function __construct(
        public string $title,
        public string $content,
    ) {
    }
}

final readonly class MixedScalarsDto
{
    public function __construct(
        public int $count,
        public float $score,
        public bool $enabled,
        public string $label,
    ) {
    }
}

final readonly class NullableDto
{
    public function __construct(
        public ?string $title,
        public ?int $count = null,
    ) {
    }
}

enum TestStringStatus: string
{
    case Todo = 'todo';
    case Done = 'done';
}

enum TestIntPriority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}

final readonly class StringEnumDto
{
    public function __construct(
        public TestStringStatus $status,
    ) {
    }
}

final readonly class IntEnumDto
{
    public function __construct(
        public TestIntPriority $priority,
    ) {
    }
}

final readonly class InnermostDto
{
    public function __construct(
        public string $name,
    ) {
    }
}

final readonly class NestedWrapperDto
{
    public function __construct(
        public InnermostDto $inner,
    ) {
    }
}

final readonly class DescribedDto
{
    public function __construct(
        #[Schema(description: 'Short title, up to 80 chars')]
        public string $title,
    ) {
    }
}

enum NullableTestEnum: string
{
    case Pending = 'pending';
    case Active = 'active';
}

final readonly class NullableEnumDto
{
    public function __construct(
        public ?NullableTestEnum $status = null,
    ) {
    }
}

final readonly class NullableNestedDto
{
    public function __construct(
        public ?InnermostDto $inner = null,
    ) {
    }
}

class NoConstructorDto
{
}

final readonly class CamelCaseDtoName
{
    public function __construct(
        public string $field,
    ) {
    }
}

final class UntypedPropertyDto
{
    public function __construct(
        public $stuff,
    ) {
    }
}

final readonly class UnionDto
{
    public function __construct(
        public string|int $value,
    ) {
    }
}

final readonly class IntersectionDto
{
    public function __construct(
        public \Stringable&\Countable $value,
    ) {
    }
}

enum NonBackedEnum
{
    case A;
    case B;
}

final readonly class NonBackedEnumDto
{
    public function __construct(
        public NonBackedEnum $value,
    ) {
    }
}

final readonly class BareArrayDto
{
    public function __construct(
        public array $items,
    ) {
    }
}

final readonly class CircularA
{
    public function __construct(
        public ?CircularA $self = null,
    ) {
    }
}
