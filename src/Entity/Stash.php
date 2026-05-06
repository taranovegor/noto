<?php

namespace App\Entity;

use App\Enum\RefType;
use App\Enum\StashType;
use App\Repository\StashRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StashRepository::class)]
#[ORM\Table(name: 'stashes')]
class Stash implements ReferenceableInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(length: 10)]
    public StashType $type;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $content;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    public bool $pinned;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $expiresAt;

    public function __construct(StashType $type)
    {
        $this->initRef(RefType::Stash);
        $this->type = $type;
        $this->content = null;
        $this->pinned = false;
        $this->expiresAt = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }
}
