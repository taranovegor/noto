<?php

namespace App\Tests\Unit\Component\Ai\Store\Document;

use App\Component\Ai\Store\Config\IndexableConfig;
use App\Component\Ai\Store\Document\TextDocumentFactory;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Document\Metadata;

class TextDocumentFactoryTest extends TestCase
{
    public function testCreateReturnsTextDocumentWithFieldContent(): void
    {
        $task = new Task('Task name');
        $task->note = 'Task note';

        $factory = new TextDocumentFactory(new IndexableConfig([
            Task::class => ['fields' => ['name', 'note'], 'identifierField' => 'id'],
        ]));

        $doc = $factory->create($task);

        $this->assertSame("Task name\nTask note", $doc->getContent());
    }

    public function testCreateSetsParentIdFromRef(): void
    {
        $task = new Task('Task name');

        $factory = new TextDocumentFactory(new IndexableConfig([
            Task::class => ['fields' => ['name'], 'identifierField' => 'id'],
        ]));

        $doc = $factory->create($task);

        $this->assertSame($task->getRef()->id->toString(), $doc->getMetadata()[Metadata::KEY_PARENT_ID]);
    }

    public function testCreateSkipsEmptyFieldValues(): void
    {
        $task = new Task('Task name');
        $task->note = '';

        $factory = new TextDocumentFactory(new IndexableConfig([
            Task::class => ['fields' => ['name', 'note'], 'identifierField' => 'id'],
        ]));

        $doc = $factory->create($task);

        $this->assertSame('Task name', $doc->getContent());
    }

    public function testCreateThrowsWhenEntityNotInConfig(): void
    {
        $task = new Task('Task name');

        $factory = new TextDocumentFactory(new IndexableConfig([]));

        $this->expectException(\InvalidArgumentException::class);

        $factory->create($task);
    }

    public function testCreateThrowsWhenAllFieldValuesAreEmpty(): void
    {
        $task = new Task('Task name');

        $factory = new TextDocumentFactory(new IndexableConfig([
            Task::class => ['fields' => ['note'], 'identifierField' => 'id'],
        ]));
        $task->note = '';

        $this->expectException(\RuntimeException::class);

        $factory->create($task);
    }
}
