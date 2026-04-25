<?php

namespace App\Entity;

use App\Contract\HasUpdatedAtInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'embeddings')]
#[ORM\Index(
    name: 'vector_idx',
    columns: ['vector'],
    /* @see Version20260425163922::addVectorIndex() */
)]
#[ORM\HasLifecycleCallbacks]
class Embedding implements HasUpdatedAtInterface
{
    use UidTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\ManyToOne(targetEntity: Ref::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $parent;

    /**
     * @var array<float>
     */
    #[ORM\Column(type: 'vector', length: 1024)]
    public private(set) array $vector;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    public private(set) array $metadata = [];

    /**
     * @param array<float>         $vector
     * @param array<string, mixed> $metadata
     */
    public function __construct(Uuid $id, Ref $parent, array $vector, array $metadata)
    {
        $this->id = $id;
        $this->vector = $vector;
        $this->parent = $parent;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }
}
