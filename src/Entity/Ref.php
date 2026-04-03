<?php

namespace App\Entity;

use App\Enum\RefType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'refs')]
class Ref
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\Column(length: 50, enumType: RefType::class)]
    public private(set) RefType $type;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    /** @var Collection<int, Link> */
    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'source', orphanRemoval: true)]
    public private(set) Collection $linksAsSource;

    /** @var Collection<int, Link> */
    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'target', orphanRemoval: true)]
    public private(set) Collection $linksAsTarget;

    public function __construct(RefType $type)
    {
        $this->id = Uuid::v7();
        $this->type = $type;
        $this->createdAt = new \DateTimeImmutable();
        $this->linksAsSource = new ArrayCollection();
        $this->linksAsTarget = new ArrayCollection();
    }
}
