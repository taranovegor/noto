<?php

namespace App\Dto\Note;

use App\Component\Searcher\Attribute\Searchable;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Service\Note\NoteSearchDefinition;

#[Searchable(definition: NoteSearchDefinition::class)]
final readonly class SearchNoteDto extends AbstractSearchDto
{
}
