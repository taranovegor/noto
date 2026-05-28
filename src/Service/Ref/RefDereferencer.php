<?php

namespace App\Service\Ref;

use App\Entity\Ref;
use App\Service\ReferenceableRegistry;
use Doctrine\Persistence\ManagerRegistry;

final readonly class RefDereferencer
{
    public function __construct(
        private ManagerRegistry $registry,
        private ReferenceableRegistry $referenceableRegistry,
    ) {
    }

    public function dereference(Ref $ref): ?object
    {
        if (!$this->referenceableRegistry->hasClass($ref->type)) {
            return null;
        }

        $class = $this->referenceableRegistry->getClass($ref->type);

        return $this->registry->getRepository($class)->find($ref->id);
    }
}
