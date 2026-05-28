<?php

namespace App\Tests\Unit\Service\Extraction;

use App\Enum\RefType;
use App\Service\Extraction\Target\ExtractionTargetHandler;
use App\Service\Extraction\Target\TargetHandlerRegistry;
use PHPUnit\Framework\TestCase;

class TargetHandlerRegistryTest extends TestCase
{
    public function testGetReturnsMatchingHandler(): void
    {
        $noteHandler = $this->createStub(ExtractionTargetHandler::class);
        $noteHandler->method('supports')->willReturn(true);

        $taskHandler = $this->createStub(ExtractionTargetHandler::class);
        $taskHandler->method('supports')->willReturn(false);

        $registry = new TargetHandlerRegistry([$taskHandler, $noteHandler]);

        $this->assertSame($noteHandler, $registry->get(RefType::Note));
    }

    public function testGetFirstMatchingHandler(): void
    {
        $first = $this->createStub(ExtractionTargetHandler::class);
        $first->method('supports')->willReturn(true);

        $second = $this->createMock(ExtractionTargetHandler::class);
        $second->expects($this->never())->method('supports');

        $registry = new TargetHandlerRegistry([$first, $second]);

        $this->assertSame($first, $registry->get(RefType::Note));
    }

    public function testGetThrowsWhenNoHandlerMatches(): void
    {
        $handler = $this->createStub(ExtractionTargetHandler::class);
        $handler->method('supports')->willReturn(false);

        $registry = new TargetHandlerRegistry([$handler]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported target type');

        $registry->get(RefType::Note);
    }

    public function testGetSchemaClass(): void
    {
        $handler = $this->createStub(ExtractionTargetHandler::class);
        $handler->method('supports')->willReturn(true);
        $handler->method('getOutputSchema')->willReturn('SomeSchema');

        $registry = new TargetHandlerRegistry([$handler]);

        $this->assertSame('SomeSchema', $registry->getSchemaClass(RefType::Note));
    }
}
