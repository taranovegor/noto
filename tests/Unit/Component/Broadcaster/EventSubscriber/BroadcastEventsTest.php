<?php

namespace App\Tests\Unit\Component\Broadcaster\EventSubscriber;

use App\Component\Broadcaster\Attribute\Broadcastable;
use App\Component\Broadcaster\BroadcasterInterface;
use App\Component\Broadcaster\Config\BroadcastableConfig;
use App\Component\Broadcaster\Enum\BroadcastChannel;
use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\EventSubscriber\BroadcastEvents;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizer;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\UnitOfWork;
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
            ->with('notes', BroadcastChannel::Events->value, ['id' => '123'], BroadcastEvent::Created);

        $config = new BroadcastableConfig([$entity::class => 'notes']);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster, $config);
        $subscriber->postPersist($this->createPostPersistEventArgs($entity));
    }

    public function testPostUpdateBroadcastsUpdatedEvent(): void
    {
        $entity = new #[Broadcastable('tasks')] class {
            public string $name = 'test';
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);
        $inner->method('supports')->willReturn(true);
        $inner->method('normalize')->willReturn(['name' => 'Updated task']);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->once())
            ->method('broadcast')
            ->with('tasks', BroadcastChannel::Events->value, ['name' => 'Updated task'], BroadcastEvent::Updated);

        $config = new BroadcastableConfig([$entity::class => 'tasks']);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster, $config);
        $subscriber->postUpdate($this->createPostUpdateEventArgs($entity, ['name' => ['old', 'new']]));
    }

    public function testPostUpdateSkipsUnchangedEntity(): void
    {
        $entity = new #[Broadcastable('tasks')] class {
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->never())->method('broadcast');

        $config = new BroadcastableConfig([$entity::class => 'tasks']);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster, $config);
        $subscriber->postUpdate($this->createPostUpdateEventArgs($entity, []));
    }

    public function testPreRemoveBroadcastsDeletedEvent(): void
    {
        $entity = new #[Broadcastable('tasks')] class {
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);
        $inner->method('supports')->willReturn(true);
        $inner->method('normalize')->willReturn(['id' => '123']);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->once())
            ->method('broadcast')
            ->with('tasks', BroadcastChannel::Events->value, ['id' => '123'], BroadcastEvent::Deleted);

        $config = new BroadcastableConfig([$entity::class => 'tasks']);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster, $config);
        $subscriber->preRemove($this->createPreRemoveEventArgs($entity));
    }

    public function testSkipsEntityWithoutBroadcastableAttribute(): void
    {
        $entity = new class {
        };

        $inner = $this->createStub(BroadcastNormalizerInterface::class);

        $broadcaster = $this->createMock(BroadcasterInterface::class);
        $broadcaster->expects($this->never())->method('broadcast');

        $config = new BroadcastableConfig([]);

        $subscriber = new BroadcastEvents(new BroadcastNormalizer([$inner]), $broadcaster, $config);
        $subscriber->postPersist($this->createPostPersistEventArgs($entity));
    }

    private function createPostPersistEventArgs(object $entity): PostPersistEventArgs
    {
        return new PostPersistEventArgs($entity, $this->createStub(ObjectManager::class));
    }

    /**
     * @param array<string, array{mixed, mixed}> $changeSet
     */
    private function createPostUpdateEventArgs(object $entity, array $changeSet): PostUpdateEventArgs
    {
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getEntityChangeSet')->willReturn($changeSet);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        return new PostUpdateEventArgs($entity, $entityManager);
    }

    private function createPreRemoveEventArgs(object $entity): PreRemoveEventArgs
    {
        return new PreRemoveEventArgs($entity, $this->createStub(ObjectManager::class));
    }
}
