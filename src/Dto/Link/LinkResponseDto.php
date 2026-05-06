<?php

namespace App\Dto\Link;

use App\Enum\LinkKind;
use App\Enum\RefType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class LinkResponseDto
{
    public function __construct(
        #[Groups(['link:read'])]
        public Uuid $id,
        #[Groups(['link:read'])]
        public Uuid $sourceId,
        #[Groups(['link:read'])]
        public RefType $sourceType,
        #[Groups(['link:read'])]
        public Uuid $targetId,
        #[Groups(['link:read'])]
        public RefType $targetType,
        #[Groups(['link:read'])]
        public LinkKind $kind,
        #[Groups(['link:read'])]
        public \DateTimeInterface $createdAt,
    ) {
    }
}
