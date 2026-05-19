<?php

namespace App\Service\Note;

use App\Dto\Note\AttachNoteAttachmentsDto;
use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Attachment;
use App\Entity\Note;
use App\Enum\LinkKind;
use App\Exception\EntityNotFoundException;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class NoteManager
{
    public function __construct(
        private NoteRepository $noteRepository,
        private LinkerInterface $linker,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateNoteDto $dto): Note
    {
        $note = new Note($dto->content);

        $this->noteRepository->add($note);

        foreach ($dto->attachments ?? [] as $attachment) {
            $this->linker->link($note->getRef(), $attachment->getRef(), LinkKind::Ownership);
        }

        $this->flusher->flush();

        return $note;
    }

    public function get(Uuid $id): Note
    {
        return $this->noteRepository->find($id) ?? throw new EntityNotFoundException(Note::class, $id);
    }

    public function update(Note $note, UpdateNoteDto $dto): void
    {
        if (null !== $dto->content) {
            $note->content = $dto->content;
        }

        $this->flusher->flush();
    }

    public function attach(Note $note, AttachNoteAttachmentsDto $dto): void
    {
        foreach ($dto->attachments as $attachment) {
            $this->linker->link($note->getRef(), $attachment->getRef(), LinkKind::Ownership);
        }
        $this->flusher->flush();
    }

    public function detach(Note $note, Attachment $attachment): void
    {
        $this->linker->unlink($note->getRef(), $attachment->getRef(), LinkKind::Ownership);
        $this->flusher->flush();
    }
}
