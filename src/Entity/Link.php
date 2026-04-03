<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'links')]
#[ORM\UniqueConstraint(columns: ['source_id', 'target_id', 'relation_type'])]
class Link
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ref::class, inversedBy: 'linksAsSource')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $source;

    #[ORM\ManyToOne(targetEntity: Ref::class, inversedBy: 'linksAsTarget')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $target;

    #[ORM\Column(length: 64)]
    public private(set) string $relationType;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(Ref $source, Ref $target)
    {
        $this->id = Uuid::v7();
        $this->source = $source;
        $this->target = $target;
        $this->relationType = sprintf('%s_to_%s', $source->type->value, $target->type->value);
        $this->createdAt = new \DateTimeImmutable();
    }
}
