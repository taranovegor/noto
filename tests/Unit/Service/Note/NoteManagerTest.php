<?php

namespace App\Tests\Unit\Service\Note;

use App\Dto\Note\AttachNoteAttachmentsDto;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Attachment;
use App\Entity\Note;
use App\Entity\Notebook;
use App\Exception\EntityNotFoundException;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Note\NoteManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NoteManagerTest extends TestCase
{
    private function makeNotebook(): Notebook
    {
        return new Notebook('NB', 'Description');
    }

    private function makeManager(
        ?NoteRepository $repo = null,
        ?LinkerInterface $linker = null,
        ?Flusher $flusher = null,
    ): NoteManager {
        return new NoteManager(
            $repo ?? $this->createStub(NoteRepository::class),
            $linker ?? $this->createStub(LinkerInterface::class),
            $flusher ?? $this->createStub(Flusher::class),
        );
    }

    public function testCreateNoteWithNoAttachments(): void
    {
        $repo = $this->createMock(NoteRepository::class);
        $flusher = $this->createMock(Flusher::class);
        $repo->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager($repo, flusher: $flusher);
        $dto = new CreateNoteDto(title: 'Test Title', content: 'Test Content');
        $result = $manager->create($this->makeNotebook(), $dto);
        $this->assertInstanceOf(Note::class, $result);
        $this->assertEquals('Test Title', $result->title);
        $this->assertEquals('Test Content', $result->content);
    }

    public function testGetNoteReturnsExistingNote(): void
    {
        $id = Uuid::v7();
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $repo = $this->createMock(NoteRepository::class);
        $repo->expects($this->once())->method('find')->with($id)->willReturn($note);
        $manager = $this->makeManager($repo);
        $result = $manager->get($id);
        $this->assertSame($note, $result);
    }

    public function testGetNoteThrowsOnNotFound(): void
    {
        $id = Uuid::v7();
        $repo = $this->createMock(NoteRepository::class);
        $repo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $manager = $this->makeManager($repo);
        $this->expectException(EntityNotFoundException::class);
        $manager->get($id);
    }

    public function testUpdateNoteUpdatesFields(): void
    {
        $note = new Note($this->makeNotebook(), 'Old Title', 'Old Content');
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager(flusher: $flusher);
        $dto = new UpdateNoteDto(title: 'New Title', content: 'New Content');
        $manager->update($note, $dto);
        $this->assertEquals('New Title', $note->title);
        $this->assertEquals('New Content', $note->content);
    }

    public function testUpdateNoteDoesNotChangeFieldsIfNull(): void
    {
        $note = new Note($this->makeNotebook(), 'Original Title', 'Original Content');
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager(flusher: $flusher);
        $dto = new UpdateNoteDto(title: null, content: null);
        $manager->update($note, $dto);
        $this->assertEquals('Original Title', $note->title);
        $this->assertEquals('Original Content', $note->content);
    }

    public function testAttachNoteCreatesLinks(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $attachment = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);
        $linker->expects($this->once())->method('link');
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager(linker: $linker, flusher: $flusher);
        $dto = new AttachNoteAttachmentsDto(attachments: [$attachment]);
        $manager->attach($note, $dto);
    }

    public function testDetachNoteRemovesLink(): void
    {
        $note = new Note($this->makeNotebook(), 'Title', 'Content');
        $attachment = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);
        $linker->expects($this->once())->method('unlink');
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager(linker: $linker, flusher: $flusher);
        $manager->detach($note, $attachment);
    }
}
