<?php

namespace App\Tests\Unit\Component\Ai\Store\EventSubscriber;

use App\Component\Ai\Store\Config\IndexableConfig;
use App\Component\Ai\Store\Document\IndexableReference;
use App\Component\Ai\Store\EventSubscriber\IndexableSubscriber;
use App\Component\Ai\Store\Message\IndexObject;
use App\Entity\Task;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class IndexableSubscriberTest extends TestCase
{
    private MessageBusInterface $bus;
    private IndexableSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);

        $this->subscriber = new IndexableSubscriber(
            new IndexableConfig([Task::class => ['fields' => ['name', 'note'], 'identifierField' => 'id']]),
            $this->bus,
            $this->createStub(LoggerInterface::class),
        );
    }

    private function makeObjectManager(): ObjectManager
    {
        return $this->createStub(ObjectManager::class);
    }

    private function makeEnvelope(Task $task): Envelope
    {
        $taskId = (string) $task->getRef()->id;

        return new Envelope(new IndexObject(new IndexableReference(Task::class, $taskId)));
    }

    public function testPostPersistDispatchesForIndexableEntity(): void
    {
        $task = new Task('Test');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(IndexObject::class))
            ->willReturn($this->makeEnvelope($task));

        $this->subscriber->postPersist(new PostPersistEventArgs($task, $this->makeObjectManager()));
    }

    public function testPostPersistSkipsNonIndexableEntity(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->subscriber->postPersist(new PostPersistEventArgs(new \stdClass(), $this->makeObjectManager()));
    }

    public function testPreUpdateQueuesEntityWhenIndexableFieldChanged(): void
    {
        $task = new Task('Test');

        $preArgs = $this->createStub(PreUpdateEventArgs::class);
        $preArgs->method('getObject')->willReturn($task);
        $preArgs->method('getEntityChangeSet')->willReturn(['name' => ['old', 'new']]);

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->makeEnvelope($task));

        $this->subscriber->preUpdate($preArgs);
        $this->subscriber->postUpdate(new PostUpdateEventArgs($task, $this->makeObjectManager()));
    }

    public function testPreUpdateSkipsWhenNoIndexableFieldChanged(): void
    {
        $task = new Task('Test');

        $preArgs = $this->createStub(PreUpdateEventArgs::class);
        $preArgs->method('getObject')->willReturn($task);
        $preArgs->method('getEntityChangeSet')->willReturn(['status' => ['todo', 'done']]);

        $this->bus->expects($this->never())->method('dispatch');

        $this->subscriber->preUpdate($preArgs);
    }

    public function testPreUpdateSkipsNonIndexableEntity(): void
    {
        $preArgs = $this->createStub(PreUpdateEventArgs::class);
        $preArgs->method('getObject')->willReturn(new \stdClass());

        $this->bus->expects($this->never())->method('dispatch');

        $this->subscriber->preUpdate($preArgs);
    }

    public function testPostUpdateSkipsEntityNotInQueue(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->subscriber->postUpdate(new PostUpdateEventArgs(new Task('Test'), $this->makeObjectManager()));
    }

    public function testResetClearsUpdateQueue(): void
    {
        $task = new Task('Test');

        $preArgs = $this->createStub(PreUpdateEventArgs::class);
        $preArgs->method('getObject')->willReturn($task);
        $preArgs->method('getEntityChangeSet')->willReturn(['name' => ['old', 'new']]);

        $this->subscriber->preUpdate($preArgs);
        $this->subscriber->reset();

        $this->bus->expects($this->never())->method('dispatch');

        $this->subscriber->postUpdate(new PostUpdateEventArgs($task, $this->makeObjectManager()));
    }
}
