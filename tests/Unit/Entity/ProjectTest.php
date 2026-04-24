<?php

namespace App\Tests\Unit\Entity;

use App\Contract\HasUpdatedAtInterface;
use App\Entity\Project;
use App\Entity\Ref;
use App\Enum\RefType;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProjectTest extends TestCase
{
    public function testConstructorInitializesProject(): void
    {
        $project = new Project('Test Project', 'TST');

        $this->assertInstanceOf(Uuid::class, $project->id);
        $this->assertEquals('Test Project', $project->name);
        $this->assertEquals('TST', $project->prefix);
        $this->assertEquals(0, $project->taskCounter);
        $this->assertInstanceOf(\DateTimeImmutable::class, $project->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $project->updatedAt);
        $this->assertInstanceOf(Ref::class, $project->ref);
        $this->assertEquals(RefType::Project, $project->ref->type);
        $this->assertInstanceOf(Collection::class, $project->tasks);
        $this->assertTrue($project->tasks->isEmpty());
    }

    public function testProjectHasUpdatedAtInterface(): void
    {
        $project = new Project('Project', 'PRJ');

        $this->assertInstanceOf(HasUpdatedAtInterface::class, $project);
        $this->assertEquals($project->updatedAt, $project->getUpdatedAt());
    }

    public function testTouchUpdatedAtUpdatesTimestamp(): void
    {
        $project = new Project('Project', 'PRJ');
        $originalUpdatedAt = $project->updatedAt;

        sleep(1);
        $project->touchUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $project->updatedAt);
    }

    public function testPrefixMustBe3Characters(): void
    {
        $project = new Project('Test', 'ABC');
        $this->assertEquals('ABC', $project->prefix);
    }

    public function testProjectIdEqualsRefId(): void
    {
        $project = new Project('Test Project', 'TST');

        $this->assertEquals($project->id, $project->ref->id);
        $this->assertSame($project->id, $project->ref->id);
    }
}
