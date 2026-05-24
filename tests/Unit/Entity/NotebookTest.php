<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Notebook;
use App\Entity\Ref;
use App\Enum\RefType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NotebookTest extends TestCase
{
    public function testConstructorInitializesNotebook(): void
    {
        $notebook = new Notebook('Title', 'Description');
        $this->assertInstanceOf(Uuid::class, $notebook->id);
        $this->assertEquals('Title', $notebook->title);
        $this->assertEquals('Description', $notebook->description);
        $this->assertInstanceOf(Ref::class, $notebook->ref);
        $this->assertEquals(RefType::Notebook, $notebook->ref->type);
        $this->assertInstanceOf(\DateTimeImmutable::class, $notebook->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $notebook->updatedAt);
    }

    public function testNotebookRefTypeIsCorrect(): void
    {
        $notebook = new Notebook('Title', 'Description');
        $this->assertInstanceOf(Ref::class, $notebook->ref);
        $this->assertEquals(RefType::Notebook, $notebook->ref->type);
    }

    public function testNotebookIdEqualsRefId(): void
    {
        $notebook = new Notebook('Title', 'Description');
        $this->assertEquals($notebook->id, $notebook->ref->id);
        $this->assertSame($notebook->id, $notebook->ref->id);
    }

    public function testTouchUpdatedAtUpdatesTimestamp(): void
    {
        $notebook = new Notebook('Title', 'Description');
        $originalUpdatedAt = $notebook->updatedAt;
        sleep(1);
        $notebook->touchUpdatedAt();
        $this->assertGreaterThan($originalUpdatedAt, $notebook->updatedAt);
    }

    public function testGetUpdatedAtReturnsTimestamp(): void
    {
        $notebook = new Notebook('Title', 'Description');
        $updatedAt = $notebook->getUpdatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updatedAt);
        $this->assertEquals($notebook->updatedAt, $updatedAt);
    }
}
