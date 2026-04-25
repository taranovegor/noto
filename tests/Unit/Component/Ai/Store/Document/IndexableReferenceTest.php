<?php

namespace App\Tests\Unit\Component\Ai\Store\Document;

use App\Component\Ai\Store\Document\IndexableReference;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class IndexableReferenceTest extends TestCase
{
    public function testToStringReturnsClassAtId(): void
    {
        $task = new Task('Test');
        $taskId = (string) $task->getRef()->id;
        $source = new IndexableReference(Task::class, $taskId);

        $this->assertSame(Task::class.'@'.$taskId, (string) $source);
    }

    public function testFromStringParsesValidSource(): void
    {
        $uuid = Uuid::v7();
        $source = IndexableReference::fromString(Task::class.'@'.$uuid->toString());

        $this->assertSame(Task::class, $source->objectClass);
        $this->assertSame($uuid->toString(), $source->objectId);
    }

    public function testFromStringRoundTrip(): void
    {
        $task = new Task('Test');
        $taskId = (string) $task->getRef()->id;
        $original = new IndexableReference(Task::class, $taskId);
        $parsed = IndexableReference::fromString((string) $original);

        $this->assertSame($original->objectClass, $parsed->objectClass);
        $this->assertSame($original->objectId, $parsed->objectId);
    }

    public function testFromStringThrowsWhenMissingSeparator(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        IndexableReference::fromString(Task::class);
    }

    public function testFromStringThrowsWhenClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        IndexableReference::fromString('App\Entity\NonExistent@'.Uuid::v7()->toString());
    }
}
