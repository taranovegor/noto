<?php

namespace App\Contract;

interface HasExtractionInstructions
{
    public function getExtractionInstructions(): ?string;
}
