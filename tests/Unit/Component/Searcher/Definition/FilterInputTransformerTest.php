<?php

namespace App\Tests\Unit\Component\Searcher\Definition;

use App\Component\Searcher\Definition\FilterInputTransformerInterface;
use App\Component\Searcher\Enum\FilterOperator;
use PHPUnit\Framework\TestCase;

class FilterInputTransformerTest extends TestCase
{
    public function testFilterInputTransformerIsInvokableWithCorrectSignature(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public bool $invoked = false;
            public FilterOperator $receivedOperator;
            public mixed $receivedValue;

            public function __invoke($operator, mixed $value): mixed
            {
                $this->invoked = true;
                $this->receivedOperator = $operator;
                $this->receivedValue = $value;

                return strtoupper($value);
            }
        };

        $result = $transformer(FilterOperator::Eq, 'test-value');

        $this->assertTrue($transformer->invoked);
        $this->assertSame(FilterOperator::Eq, $transformer->receivedOperator);
        $this->assertEquals('test-value', $transformer->receivedValue);
        $this->assertEquals('TEST-VALUE', $result);
    }

    public function testFilterInputTransformerTransformsStringValue(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public function __invoke($operator, mixed $value): mixed
            {
                // Simulate a trimming transformer
                return is_string($value) ? trim($value) : $value;
            }
        };

        $result = $transformer(FilterOperator::Eq, '  hello world  ');

        $this->assertEquals('hello world', $result);
    }

    public function testFilterInputTransformerTransformsArrayValue(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public function __invoke($operator, mixed $value): mixed
            {
                // Simulate array transformation (e.g., converting array values to uppercase)
                if (is_array($value)) {
                    return array_map('strtoupper', $value);
                }

                return $value;
            }
        };

        $result = $transformer(FilterOperator::In, ['apple', 'banana', 'cherry']);

        $this->assertEquals(['APPLE', 'BANANA', 'CHERRY'], $result);
    }

    public function testFilterInputTransformerRespondsToOperatorType(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public function __invoke($operator, mixed $value): mixed
            {
                return match ($operator) {
                    FilterOperator::In, FilterOperator::NotIn => is_array($value) ? $value : [$value],
                    default => $value,
                };
            }
        };

        // For In/NotIn operators, should wrap scalar in array
        $result1 = $transformer(FilterOperator::In, 'single-value');
        $this->assertEquals(['single-value'], $result1);

        // For other operators, should return as is
        $result2 = $transformer(FilterOperator::Eq, 'single-value');
        $this->assertEquals('single-value', $result2);
    }

    public function testFilterInputTransformerCanReturnComplexData(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public function __invoke($operator, mixed $value): mixed
            {
                // Simulate vectorizing text to array of floats
                if (is_string($value)) {
                    return array_map(fn () => mt_rand(0, 100) / 100.0, range(1, 5));
                }

                return $value;
            }
        };

        $result = $transformer(FilterOperator::Like, 'search term');

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        foreach ($result as $value) {
            $this->assertIsFloat($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(1, $value);
        }
    }

    public function testFilterInputTransformerCanPassThroughValue(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public function __invoke($operator, mixed $value): mixed
            {
                // Pass-through transformer - returns value unchanged
                return $value;
            }
        };

        $stringValue = 'original-string';
        $arrayValue = [1, 2, 3];

        $this->assertEquals($stringValue, $transformer(FilterOperator::Eq, $stringValue));
        $this->assertEquals($arrayValue, $transformer(FilterOperator::In, $arrayValue));
    }

    public function testFilterInputTransformerValidatesAndTransforms(): void
    {
        $transformer = new class implements FilterInputTransformerInterface {
            public function __invoke($operator, mixed $value): mixed
            {
                // Transform only valid UUIDs, return empty string for invalid
                if (!is_string($value)) {
                    return '';
                }

                $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

                return preg_match($uuidRegex, $value) ? strtolower($value) : '';
            }
        };

        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $invalidUuid = 'not-a-uuid';

        $this->assertEquals(strtolower($validUuid), $transformer(FilterOperator::Eq, $validUuid));
        $this->assertEquals('', $transformer(FilterOperator::Eq, $invalidUuid));
    }
}
