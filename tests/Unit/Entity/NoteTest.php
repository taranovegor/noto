<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Note;
use App\Entity\Notebook;
use App\Entity\Ref;
use App\Enum\RefType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NoteTest extends TestCase
{
    private function makeNotebook(): Notebook
    {
        return new Notebook('NB', 'Description');
    }

    public function testConstructorInitializesNote(): void
    {
        $notebook = $this->makeNotebook();
        $note = new Note($notebook, 'Title', 'Content');
        $this->assertInstanceOf(Uuid::class, $note->id);
        $this->assertSame($notebook, $note->notebook);
        $this->assertEquals('Title', $note->title);
        $this->assertEquals('Content', $note->content);
        $this->assertInstanceOf(Ref::class, $note->ref);
        $this->assertEquals(RefType::Note, $note->ref->type);
        $this->assertInstanceOf(\DateTimeImmutable::class, $note->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $note->updatedAt);
    }

    public function testNoteRefTypeIsCorrect(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $this->assertInstanceOf(Ref::class, $note->ref);
        $this->assertEquals(RefType::Note, $note->ref->type);
    }

    public function testNoteIdEqualsRefId(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $this->assertEquals($note->id, $note->ref->id);
        $this->assertSame($note->id, $note->ref->id);
    }

    public function testGetContentReturnsContent(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $this->assertEquals('Content', $note->getContent());
    }

    public function testTouchUpdatedAtUpdatesTimestamp(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $originalUpdatedAt = $note->updatedAt;
        sleep(1);
        $note->touchUpdatedAt();
        $this->assertGreaterThan($originalUpdatedAt, $note->updatedAt);
    }

    public function testGetUpdatedAtReturnsTimestamp(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $updatedAt = $note->getUpdatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updatedAt);
        $this->assertEquals($note->updatedAt, $updatedAt);
    }
}
