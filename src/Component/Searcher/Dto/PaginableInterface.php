<?php

namespace App\Component\Searcher\Dto;

use App\Component\Searcher\Model\PaginationDetails;

interface PaginableInterface extends SearchableInterface
{
    public function getPagination(): PaginationDetails;
}
