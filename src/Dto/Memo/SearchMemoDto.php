<?php

namespace App\Dto\Memo;

use App\Component\Searcher\Attribute\Searchable;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Service\Memo\MemoSearchDefinition;

#[Searchable(definition: MemoSearchDefinition::class)]
final readonly class SearchMemoDto extends AbstractSearchDto
{
}
