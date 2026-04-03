<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\SortInstruction;

interface SortableInterface extends SearchableInterface
{
    /**
     * @return SortInstruction[]
     */
    public function getSorting(): array;
}
