<?php

namespace App\Tests\Unit\Service\Stash;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use App\Dto\Stash\StashResponseDto;
use App\Entity\Stash;
use App\Enum\StashType;
use App\Factory\Stash\StashResponseDtoFactory;
use App\Service\Stash\StashBroadcastNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class StashBroadcastNormalizerTest extends TestCase
{
    public function testSupportsReturnsTrueForStashCreated(): void
    {
        $normalizer = new StashBroadcastNormalizer(
            $this->createStub(StashResponseDtoFactory::class),
            $this->createStub(NormalizerInterface::class),
        );
        $stash = new Stash(StashType::Text);

        $this->assertTrue($normalizer->supports(BroadcastEvent::Created, $stash));
    }

    public function testSupportsReturnsTrueForStashUpdated(): void
    {
        $normalizer = new StashBroadcastNormalizer(
            $this->createStub(StashResponseDtoFactory::class),
            $this->createStub(NormalizerInterface::class),
        );
        $stash = new Stash(StashType::Text);

        $this->assertTrue($normalizer->supports(BroadcastEvent::Updated, $stash));
    }

    public function testSupportsReturnsFalseForStashDeleted(): void
    {
        $normalizer = new StashBroadcastNormalizer(
            $this->createStub(StashResponseDtoFactory::class),
            $this->createStub(NormalizerInterface::class),
        );
        $stash = new Stash(StashType::Text);

        $this->assertFalse($normalizer->supports(BroadcastEvent::Deleted, $stash));
    }

    public function testSupportsReturnsFalseForOtherEntity(): void
    {
        $normalizer = new StashBroadcastNormalizer(
            $this->createStub(StashResponseDtoFactory::class),
            $this->createStub(NormalizerInterface::class),
        );

        $this->assertFalse($normalizer->supports(BroadcastEvent::Created, new \stdClass()));
    }

    public function testNormalizeReturnsArray(): void
    {
        $stash = new Stash(StashType::Text);

        $factory = $this->createMock(StashResponseDtoFactory::class);
        $factory->expects($this->once())
            ->method('create')
            ->with($stash)
            ->willReturn($dto = new StashResponseDto(
                $stash->id,
                $stash->type,
                $stash->content,
                $stash->createdAt,
                $stash->expiresAt,
                $stash->pinned,
            ));

        $serializer = $this->createMock(NormalizerInterface::class);
        $serializer->expects($this->once())
            ->method('normalize')
            ->with($dto)
            ->willReturn($expected = ['id' => 'uuid', 'type' => 'text', 'content' => null]);

        $normalizer = new StashBroadcastNormalizer($factory, $serializer);

        $this->assertSame($expected, $normalizer->normalize(BroadcastEvent::Created, $stash));
    }

    public function testImplementsInterface(): void
    {
        $normalizer = new StashBroadcastNormalizer(
            $this->createStub(StashResponseDtoFactory::class),
            $this->createStub(NormalizerInterface::class),
        );

        $this->assertInstanceOf(BroadcastNormalizerInterface::class, $normalizer);
    }
}
