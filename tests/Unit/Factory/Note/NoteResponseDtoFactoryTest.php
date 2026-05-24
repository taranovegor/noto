<?php

namespace App\Tests\Unit\Factory\Note;

use App\Entity\Note;
use App\Entity\Notebook;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Link\LinkResolver;
use PHPUnit\Framework\TestCase;

class NoteResponseDtoFactoryTest extends TestCase
{
    private NoteResponseDtoFactory $factory;
    private Notebook $notebook;

    protected function setUp(): void
    {
        $this->factory = new NoteResponseDtoFactory(
            $this->createStub(LinkResolver::class),
            new AttachmentResponseDtoFactory(),
        );
        $this->notebook = new Notebook('NB', 'Description');
    }

    public function testCreateConvertsNoteToDto(): void
    {
        $note = new Note($this->notebook, 'Test Title', 'Test Content');
        $dto = $this->factory->create($note);
        $this->assertEquals($note->id, $dto->id);
        $this->assertEquals($this->notebook->id, $dto->notebookId);
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Content', $dto->content);
    }

    public function testCreateWithNoAttachmentsReturnsNull(): void
    {
        $note = new Note($this->notebook, 'Title', 'Content');
        $dto = $this->factory->create($note);
        $this->assertNull($dto->attachments);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $note = new Note($this->notebook, 'Title', 'Content');
        $dto = $this->factory->create($note);
        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
    }
}
