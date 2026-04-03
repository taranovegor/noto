<?php

namespace App\Dto\Task;

use App\Component\Searcher\Attribute\Searchable;
use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Service\Task\TaskSearchDefinition;

#[Searchable(definition: TaskSearchDefinition::class)]
final readonly class SearchTaskDto extends AbstractSearchDto
{
}
