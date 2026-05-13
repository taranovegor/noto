<?php

namespace App\Tests\Unit\Service\Note;

use App\Dto\Note\AttachNoteAttachmentsDto;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Attachment;
use App\Entity\Note;
use App\Enum\LinkKind;
use App\Exception\EntityNotFoundException;
use App\Exception\LinkNotFoundException;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Note\NoteManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NoteManagerTest extends TestCase
{
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
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $repo->expects($this->once())->method('add');
        $linker->expects($this->never())->method('link');
        $flusher->expects($this->once())->method('flush');

        $note = $this->makeManager($repo, $linker, $flusher)->create(new CreateNoteDto(content: '# Note'));

        $this->assertInstanceOf(Note::class, $note);
        $this->assertEquals('# Note', $note->content);
    }

    public function testCreateNoteWithAttachmentsLinksOwnership(): void
    {
        $repo = $this->createMock(NoteRepository::class);
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $a1 = new Attachment();
        $a2 = new Attachment();

        $repo->expects($this->once())->method('add');
        $linker->expects($this->exactly(2))->method('link')
            ->with($this->isInstanceOf(Note::class), $this->isInstanceOf(Attachment::class), LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager($repo, $linker, $flusher)->create(
            new CreateNoteDto(content: '# Note', attachments: [$a1, $a2]),
        );
    }

    public function testGetNoteReturnsNote(): void
    {
        $note = new Note('Content');
        $repo = $this->createMock(NoteRepository::class);
        $repo->expects($this->once())->method('find')->willReturn($note);

        $this->assertSame($note, $this->makeManager(repo: $repo)->get(Uuid::v7()));
    }

    public function testGetNoteThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(NoteRepository::class);
        $repo->expects($this->once())->method('find')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);

        $this->makeManager(repo: $repo)->get(Uuid::v7());
    }

    public function testUpdateNoteContent(): void
    {
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $note = new Note('Old');
        $this->makeManager(flusher: $flusher)->update($note, new UpdateNoteDto(content: 'New'));

        $this->assertEquals('New', $note->content);
    }

    public function testUpdateNoteSkipsNullContent(): void
    {
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $note = new Note('Original');
        $this->makeManager(flusher: $flusher)->update($note, new UpdateNoteDto(content: null));

        $this->assertEquals('Original', $note->content);
    }

    public function testAttachLinksEachAttachment(): void
    {
        $note = new Note('# Note');
        $a1 = new Attachment();
        $a2 = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->exactly(2))->method('link')
            ->with($note, $this->isInstanceOf(Attachment::class), LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager(linker: $linker, flusher: $flusher)
            ->attach($note, new AttachNoteAttachmentsDto(attachments: [$a1, $a2]));
    }

    public function testDetachCallsUnlink(): void
    {
        $note = new Note('# Note');
        $attachment = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->once())->method('unlink')->with($note, $attachment, LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager(linker: $linker, flusher: $flusher)->detach($note, $attachment);
    }

    public function testDetachPropagatesLinkNotFoundException(): void
    {
        $note = new Note('# Note');
        $attachment = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->once())->method('unlink')
            ->willThrowException(new LinkNotFoundException($note, $attachment, LinkKind::Ownership));
        $flusher->expects($this->never())->method('flush');

        $this->expectException(LinkNotFoundException::class);

        $this->makeManager(linker: $linker, flusher: $flusher)->detach($note, $attachment);
    }
}
