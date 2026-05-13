<?php

namespace App\Tests\Unit\Service\Note;

use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Note;
use App\Exception\EntityNotFoundException;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Note\NoteManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NoteManagerTest extends TestCase
{
    private NoteRepository&MockObject $noteRepository;
    private Flusher&MockObject $flusher;
    private NoteManager $noteManager;

    protected function setUp(): void
    {
        $this->noteRepository = $this->createMock(NoteRepository::class);
        $this->flusher = $this->createMock(Flusher::class);

        $this->noteManager = new NoteManager(
            $this->noteRepository,
            $this->flusher,
        );
    }

    public function testCreateNoteWithValidData(): void
    {
        $content = 'This is a test note content';
        $dto = new CreateNoteDto(
            content: $content,
        );

        $this->noteRepository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Note $note) use ($content) {
                return $content === $note->content;
            }));
        $this->flusher->expects($this->once())->method('flush');

        $note = $this->noteManager->create($dto);

        $this->assertInstanceOf(Note::class, $note);
        $this->assertEquals($content, $note->content);
    }

    public function testGetNoteReturnsNote(): void
    {
        $id = Uuid::v7();
        $note = new Note('Content');

        $this->noteRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($note);
        $this->flusher->expects($this->never())->method('flush');

        $result = $this->noteManager->get($id);

        $this->assertEquals($note, $result);
    }

    public function testGetNoteThrowsEntityNotFoundExceptionWhenNotFound(): void
    {
        $id = Uuid::v7();

        $this->noteRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);
        $this->flusher->expects($this->never())->method('flush');

        $this->expectException(EntityNotFoundException::class);

        $this->noteManager->get($id);
    }

    public function testUpdateNotePartially(): void
    {
        $note = new Note('Old Content');

        $dto = new UpdateNoteDto(
            content: null,
        );

        $this->noteRepository->expects($this->never())->method('add');
        $this->flusher->expects($this->once())->method('flush');

        $this->noteManager->update($note, $dto);

        $this->assertEquals('Old Content', $note->content);
    }

    public function testUpdateNoteAllFields(): void
    {
        $note = new Note('Old Content');

        $dto = new UpdateNoteDto(
            content: 'New Content',
        );

        $this->noteRepository->expects($this->never())->method('add');
        $this->flusher->expects($this->once())->method('flush');

        $this->noteManager->update($note, $dto);

        $this->assertEquals('New Content', $note->content);
    }

    public function testUpdateNoteNoChanges(): void
    {
        $note = new Note('Content');

        $dto = new UpdateNoteDto(
            content: null,
        );

        $this->noteRepository->expects($this->never())->method('add');
        $this->flusher->expects($this->once())->method('flush');

        $this->noteManager->update($note, $dto);

        $this->assertEquals('Content', $note->content);
    }
}
