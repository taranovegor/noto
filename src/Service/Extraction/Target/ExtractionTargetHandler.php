<?php

namespace App\Service\Extraction\Target;

use App\Entity\Extraction;
use App\Entity\ReferenceableInterface;
use App\Enum\RefType;

interface ExtractionTargetHandler
{
    public function supports(RefType $type): bool;

    /** @return class-string */
    public function getOutputSchema(): string;

    public function create(Extraction $extraction, object $dto): ReferenceableInterface;
}
