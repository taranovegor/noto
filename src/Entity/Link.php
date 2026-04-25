<?php

namespace App\Entity;

use App\Enum\LinkKind;
use App\Enum\RefType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'links')]
#[ORM\UniqueConstraint(columns: ['source_id', 'target_id', 'kind'])]
#[ORM\HasLifecycleCallbacks]
class Link
{
    use UidTrait;

    #[ORM\ManyToOne(targetEntity: Ref::class, inversedBy: 'linksAsSource')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $source;

    #[ORM\Column(length: 50, enumType: RefType::class, index: true)]
    public private(set) RefType $sourceType;

    #[ORM\ManyToOne(targetEntity: Ref::class, inversedBy: 'linksAsTarget')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $target;

    #[ORM\Column(length: 50, enumType: RefType::class, index: true)]
    public private(set) RefType $targetType;

    #[ORM\Column(length: 32, enumType: LinkKind::class)]
    public private(set) LinkKind $kind;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(Ref $source, Ref $target, LinkKind $kind)
    {
        $this->id = Uuid::v7();
        $this->source = $source;
        $this->sourceType = $source->type;
        $this->target = $target;
        $this->targetType = $target->type;
        $this->kind = $kind;
        $this->createdAt = new \DateTimeImmutable();
    }
}
