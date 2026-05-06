<?php

namespace App\Dto\Link;

use App\Component\Validator\Constraint\EntityExists;
use App\Entity\Ref;
use App\Enum\LinkKind;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateLinkDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[EntityExists(entityClass: Ref::class, field: 'id')]
        #[Assert\Expression('value.toString() != this.targetId.toString()', message: 'Cannot link an entity to itself.')]
        public Uuid $sourceId,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[EntityExists(entityClass: Ref::class, field: 'id')]
        public Uuid $targetId,
        public LinkKind $kind,
    ) {
    }
}
