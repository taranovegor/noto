<?php

namespace App\Tests\Unit\Component\Broadcaster\Normalizer;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizer;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use PHPUnit\Framework\TestCase;

class BroadcastNormalizerTest extends TestCase
{
    public function testNormalizeDelegatesToMatchingNormalizer(): void
    {
        $entity = new \stdClass();
        $expectedData = ['id' => '123', 'title' => 'Test'];

        $mockNormalizer = $this->createMock(BroadcastNormalizerInterface::class);
        $mockNormalizer->expects($this->once())
            ->method('supports')
            ->with(BroadcastEvent::Created, $entity)
            ->willReturn(true);
        $mockNormalizer->expects($this->once())
            ->method('normalize')
            ->with($entity)
            ->willReturn($expectedData);

        $normalizer = new BroadcastNormalizer([$mockNormalizer]);

        $result = $normalizer->normalize(BroadcastEvent::Created, $entity);

        $this->assertSame($expectedData, $result);
    }

    public function testNormalizeReturnsFirstMatchingNormalizer(): void
    {
        $entity = new \stdClass();

        $normalizer1 = $this->createMock(BroadcastNormalizerInterface::class);
        $normalizer1->method('supports')->willReturn(false);
        $normalizer1->expects($this->never())->method('normalize');

        $normalizer2 = $this->createStub(BroadcastNormalizerInterface::class);
        $normalizer2->method('supports')->willReturn(true);
        $normalizer2->method('normalize')->willReturn(['result' => 'second']);

        $normalizer = new BroadcastNormalizer([$normalizer1, $normalizer2]);

        $result = $normalizer->normalize(BroadcastEvent::Updated, $entity);

        $this->assertSame(['result' => 'second'], $result);
    }

    public function testNormalizeThrowsWhenNoNormalizerFound(): void
    {
        $entity = new \stdClass();

        $mockNormalizer = $this->createStub(BroadcastNormalizerInterface::class);
        $mockNormalizer->method('supports')->willReturn(false);

        $normalizer = new BroadcastNormalizer([$mockNormalizer]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No broadcast normalizer found');

        $normalizer->normalize(BroadcastEvent::Created, $entity);
    }
}
