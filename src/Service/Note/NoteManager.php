<?php

namespace App\Service\Note;

use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\UpdateNoteDto;
use App\Entity\Note;
use App\Exception\EntityNotFoundException;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use Symfony\Component\Uid\Uuid;

final readonly class NoteManager
{
    public function __construct(
        private NoteRepository $noteRepository,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateNoteDto $dto): Note
    {
        $note = new Note($dto->title, $dto->content);

        $this->noteRepository->add($note);

        $this->flusher->flush();

        return $note;
    }

    public function get(Uuid $id): Note
    {
        return $this->noteRepository->find($id) ?? throw new EntityNotFoundException(Note::class, $id);
    }

    public function update(Note $note, UpdateNoteDto $dto): void
    {
        if (null !== $dto->title) {
            $note->title = $dto->title;
        }

        if (null !== $dto->content) {
            $note->content = $dto->content;
        }

        $this->flusher->flush();
    }
}
