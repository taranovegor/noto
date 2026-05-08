<?php

namespace App\Tests\Unit\Service;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use App\Entity\Ref;
use App\Entity\ReferenceableInterface;
use App\Enum\RefType;
use App\Service\DeleteBroadcastNormalizer;
use PHPUnit\Framework\TestCase;

class DeleteBroadcastNormalizerTest extends TestCase
{
    public function testSupportsReturnsTrueForDeletedWithReferenceableEntity(): void
    {
        $normalizer = new DeleteBroadcastNormalizer();
        $entity = $this->createStub(ReferenceableInterface::class);

        $this->assertTrue($normalizer->supports(BroadcastEvent::Deleted, $entity));
    }

    public function testSupportsReturnsFalseForNonDeletedEvent(): void
    {
        $normalizer = new DeleteBroadcastNormalizer();
        $entity = $this->createStub(ReferenceableInterface::class);

        $this->assertFalse($normalizer->supports(BroadcastEvent::Created, $entity));
        $this->assertFalse($normalizer->supports(BroadcastEvent::Updated, $entity));
    }

    public function testSupportsReturnsFalseForNonReferenceableEntity(): void
    {
        $normalizer = new DeleteBroadcastNormalizer();

        $this->assertFalse($normalizer->supports(BroadcastEvent::Deleted, new \stdClass()));
    }

    public function testNormalizeReturnsIdFromRef(): void
    {
        $normalizer = new DeleteBroadcastNormalizer();

        $ref = new Ref(RefType::Task);

        $entity = $this->createStub(ReferenceableInterface::class);
        $entity->method('getRef')->willReturn($ref);

        $result = $normalizer->normalize(BroadcastEvent::Deleted, $entity);

        $this->assertSame(['id' => $ref->id], $result);
    }

    public function testImplementsInterface(): void
    {
        $normalizer = new DeleteBroadcastNormalizer();

        $this->assertInstanceOf(BroadcastNormalizerInterface::class, $normalizer);
    }
}
