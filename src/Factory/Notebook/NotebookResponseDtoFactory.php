<?php

namespace App\Factory\Notebook;

use App\Dto\Notebook\NotebookResponseDto;
use App\Entity\Notebook;

readonly class NotebookResponseDtoFactory
{
    public function create(Notebook $notebook): NotebookResponseDto
    {
        return new NotebookResponseDto(
            $notebook->id,
            $notebook->title,
            $notebook->description,
            $notebook->createdAt,
            $notebook->updatedAt,
            $notebook->extractionInstructions,
        );
    }
}
