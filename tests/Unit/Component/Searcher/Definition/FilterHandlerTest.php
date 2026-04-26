<?php

namespace App\Tests\Unit\Component\Searcher\Definition;

use App\Component\Searcher\Context\DoctrineFilterContext;
use App\Component\Searcher\Context\FilterContextInterface;
use App\Component\Searcher\Definition\FilterHandlerInterface;
use App\Component\Searcher\Enum\FilterOperator;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class FilterHandlerTest extends TestCase
{
    public function testFilterHandlerIsInvokableWithCorrectSignature(): void
    {
        $handler = new class implements FilterHandlerInterface {
            public bool $invoked = false;
            public FilterContextInterface $receivedContext;
            public FilterOperator $receivedOperator;
            public mixed $receivedValue;

            public function __invoke(FilterContextInterface $context, $operator, mixed $value): void
            {
                $this->invoked = true;
                $this->receivedContext = $context;
                $this->receivedOperator = $operator;
                $this->receivedValue = $value;
            }
        };

        $queryBuilderStub = $this->createStub(QueryBuilder::class);
        $context = new DoctrineFilterContext('e', $queryBuilderStub);
        $operator = FilterOperator::Eq;
        $value = 'test-value';

        $handler($context, $operator, $value);

        $this->assertTrue($handler->invoked);
        $this->assertSame($context, $handler->receivedContext);
        $this->assertSame($operator, $handler->receivedOperator);
        $this->assertEquals('test-value', $handler->receivedValue);
    }

    public function testFilterHandlerReceivesCorrectOperatorType(): void
    {
        $receivedOperators = [];

        $handler = new class($receivedOperators) implements FilterHandlerInterface {
            public function __construct(private array &$receivedOperators)
            {
            }

            public function __invoke(FilterContextInterface $context, $operator, mixed $value): void
            {
                $this->receivedOperators[] = $operator;
            }
        };

        $queryBuilderStub = $this->createStub(QueryBuilder::class);
        $context = new DoctrineFilterContext('e', $queryBuilderStub);

        $handler($context, FilterOperator::Eq, 'value1');
        $handler($context, FilterOperator::In, ['val1', 'val2']);
        $handler($context, FilterOperator::Like, 'search%');

        $this->assertCount(3, $receivedOperators);
        $this->assertSame(FilterOperator::Eq, $receivedOperators[0]);
        $this->assertSame(FilterOperator::In, $receivedOperators[1]);
        $this->assertSame(FilterOperator::Like, $receivedOperators[2]);
    }

    public function testFilterHandlerReceivesVariousValueTypes(): void
    {
        $receivedValues = [];

        $handler = new class($receivedValues) implements FilterHandlerInterface {
            public function __construct(private array &$receivedValues)
            {
            }

            public function __invoke(FilterContextInterface $context, $operator, mixed $value): void
            {
                $this->receivedValues[] = $value;
            }
        };

        $queryBuilderStub = $this->createStub(QueryBuilder::class);
        $context = new DoctrineFilterContext('e', $queryBuilderStub);

        // Test string value
        $handler($context, FilterOperator::Eq, 'string-value');
        // Test array value
        $handler($context, FilterOperator::In, ['val1', 'val2', 'val3']);
        // Test numeric value
        $handler($context, FilterOperator::Gt, 42);
        // Test array of objects (simulated vector embedding)
        $handler($context, FilterOperator::Like, [0.1, 0.2, 0.3, 0.4]);

        $this->assertCount(4, $receivedValues);
        $this->assertEquals('string-value', $receivedValues[0]);
        $this->assertEquals(['val1', 'val2', 'val3'], $receivedValues[1]);
        $this->assertEquals(42, $receivedValues[2]);
        $this->assertEquals([0.1, 0.2, 0.3, 0.4], $receivedValues[3]);
    }
}
