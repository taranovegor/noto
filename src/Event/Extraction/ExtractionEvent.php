<?php

namespace App\Event\Extraction;

use App\Entity\Extraction;
use App\Enum\RefType;
use App\Event\ReferenceableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ExtractionEvent extends Event implements ReferenceableEventInterface
{
    public const string Created = 'entity.extraction.created';

    public function __construct(
        public readonly Extraction $extraction,
    ) {
    }

    public static function getRefType(): RefType
    {
        return RefType::Extraction;
    }
}
