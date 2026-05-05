<?php

namespace App\Tests\Unit\Component\Broadcaster\EventSubscriber;

use App\Component\Broadcaster\Attribute\Broadcastable;
use App\Component\Broadcaster\BroadcasterInterface;
use App\Component\Broadcaster\Enum\BroadcastChannel;
use App\Component\Broadcaster\EventSubscriber\BroadcastEvents;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizer;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class BroadcastEventsTest extends TestCase
{
    public function testPostPersistBroadcastsCreatedEvent(): void
    {
        $entity = new #[Broadcastable('notes')] class {
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);
        $inner->method('supports')->willReturn(true);
        $inner->method('normalize')->willReturn(['id' => '123']);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->once())
            ->method('broadcast')
            ->with('notes', BroadcastChannel::Events->value, ['id' => '123']);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster);
        $subscriber->postPersist($this->createPostPersistEventArgs($entity));
    }

    public function testPostUpdateBroadcastsUpdatedEvent(): void
    {
        $entity = new #[Broadcastable('tasks')] class {
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);
        $inner->method('supports')->willReturn(true);
        $inner->method('normalize')->willReturn(['name' => 'Updated task']);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->once())
            ->method('broadcast')
            ->with('tasks', BroadcastChannel::Events->value, ['name' => 'Updated task']);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster);
        $subscriber->postUpdate($this->createPostUpdateEventArgs($entity));
    }

    public function testSkipsEntityWithoutBroadcastableAttribute(): void
    {
        $entity = new class {
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->never())->method('broadcast');

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster);
        $subscriber->postPersist($this->createPostPersistEventArgs($entity));
    }

    private function createPostPersistEventArgs(object $entity): PostPersistEventArgs
    {
        return new PostPersistEventArgs($entity, $this->createStub(ObjectManager::class));
    }

    private function createPostUpdateEventArgs(object $entity): PostUpdateEventArgs
    {
        return new PostUpdateEventArgs($entity, $this->createStub(ObjectManager::class));
    }
}
