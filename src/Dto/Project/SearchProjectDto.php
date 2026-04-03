<?php

namespace App\Dto\Project;

use App\Component\Searcher\Attribute\Searchable;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Service\Project\ProjectSearchDefinition;

#[Searchable(definition: ProjectSearchDefinition::class)]
final readonly class SearchProjectDto extends AbstractSearchDto
{
}
