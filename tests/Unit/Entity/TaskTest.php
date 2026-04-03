<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\Ref;
use App\Entity\Task;
use App\Enum\RefType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TaskTest extends TestCase
{
    public function testConstructorInitializesTask(): void
    {
        $task = new Task('Test Task');

        $this->assertInstanceOf(Uuid::class, $task->id);
        $this->assertEquals('Test Task', $task->name);
        $this->assertInstanceOf(Ref::class, $task->ref);
        $this->assertEquals(RefType::Task, $task->ref->type);
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->updatedAt);
    }

    public function testSetProjectAssignsProjectAndCode(): void
    {
        $task = new Task('Task');
        $project = new Project('Test Project', 'TST');

        $task->setProject($project, 'TST-1');

        $this->assertEquals($project, $task->project);
        $this->assertEquals('TST-1', $task->code);
    }

    public function testTouchUpdatedAtUpdatesTimestamp(): void
    {
        $task = new Task('Task');
        $originalUpdatedAt = $task->updatedAt;

        sleep(1);
        $task->touchUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $task->updatedAt);
    }

    public function testGetUpdatedAtReturnsTimestamp(): void
    {
        $task = new Task('Task');
        $updatedAt = $task->getUpdatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $updatedAt);
        $this->assertEquals($task->updatedAt, $updatedAt);
    }

    public function testTaskPropertiesAreInitiallyNull(): void
    {
        $task = new Task('Task');

        $this->assertNull($task->project);
        $this->assertNull($task->code);
        $this->assertNull($task->deadline);
        $this->assertNull($task->priority);
    }

    public function testTaskRefTypeIsCorrect(): void
    {
        $task = new Task('Task');

        $this->assertInstanceOf(Ref::class, $task->ref);
        $this->assertEquals(RefType::Task, $task->ref->type);
    }
}
