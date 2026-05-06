<?php

namespace App\Dto\Stash;

use App\Component\Searcher\Attribute\Searchable;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Service\Stash\StashSearchDefinition;

#[Searchable(definition: StashSearchDefinition::class)]
final readonly class SearchStashDto extends AbstractSearchDto
{
}
