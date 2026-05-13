<?php

namespace App\Factory\Note;

use App\Dto\Note\NoteResponseDto;
use App\Entity\Note;

class NoteResponseDtoFactory
{
    public function create(Note $note): NoteResponseDto
    {
        return new NoteResponseDto(
            $note->id,
            $note->content,
            $note->createdAt,
            $note->updatedAt,
        );
    }
}
