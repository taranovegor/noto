<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\Ref;
use App\Entity\Task;
use App\Enum\RefType;
use PHPUnit\Framework\TestCase;

class ReferenceableTraitTest extends TestCase
{
    public function testInitRefCreatesRefWithCorrectType(): void
    {
        $task = new Task('Test Task');

        $this->assertInstanceOf(Ref::class, $task->ref);
        $this->assertEquals(RefType::Task, $task->ref->type);
    }

    public function testInitRefSetsEntityIdToRefId(): void
    {
        $task = new Task('Test Task');

        $this->assertSame($task->id, $task->ref->id);
        $this->assertEquals($task->id, $task->ref->id);
    }

    public function testInitRefCreatesUniqueRefIds(): void
    {
        $task1 = new Task('Task 1');
        $task2 = new Task('Task 2');

        $this->assertNotEquals($task1->ref->id, $task2->ref->id);
    }

    public function testInitRefCreatesRefForProject(): void
    {
        $project = new Project('Test Project', 'TST');

        $this->assertInstanceOf(Ref::class, $project->ref);
        $this->assertEquals(RefType::Project, $project->ref->type);
    }

    public function testInitRefSetsProjectRefId(): void
    {
        $project = new Project('Test Project', 'TST');

        $this->assertEquals($project->id, $project->ref->id);
    }

    public function testGetRefReturnsRef(): void
    {
        $task = new Task('Test Task');
        $ref = $task->getRef();

        $this->assertInstanceOf(Ref::class, $ref);
        $this->assertEquals(RefType::Task, $ref->type);
        $this->assertEquals($task->id, $ref->id);
    }

    public function testRefIdIsCreatedAtConstruction(): void
    {
        $task = new Task('Test Task');
        $refId = $task->ref->id;

        $this->assertNotNull($refId);
        $this->assertSame($refId, $task->id);
    }

    public function testRefCreatedAtIsSet(): void
    {
        $before = new \DateTimeImmutable();
        $task = new Task('Test Task');
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $task->ref->createdAt);
        $this->assertLessThanOrEqual($after, $task->ref->createdAt);
    }

    public function testMultipleTasksHaveDistinctRefs(): void
    {
        $task1 = new Task('Task 1');
        $task2 = new Task('Task 2');
        $task3 = new Task('Task 3');

        $refIds = [$task1->ref->id, $task2->ref->id, $task3->ref->id];
        $uniqueRefIds = array_unique($refIds);

        $this->assertCount(3, $uniqueRefIds);
    }

    public function testRefTypeIsCorrectForEachEntity(): void
    {
        $task = new Task('Task');
        $project = new Project('Project', 'PRJ');

        $this->assertEquals(RefType::Task, $task->ref->type);
        $this->assertEquals(RefType::Project, $project->ref->type);
    }
}
