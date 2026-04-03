<?php

namespace App\Entity;

use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
class Project implements HasUpdatedAtInterface
{
    use HasUpdatedAtTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\OneToOne(targetEntity: Ref::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $ref;

    #[ORM\Column(length: 255)]
    public private(set) string $name;

    #[ORM\Column(type: 'ascii_string', length: 3)]
    public private(set) string $prefix;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    public private(set) int $taskCounter = 0;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    public private(set) array $aliases = [];

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'project')]
    public private(set) Collection $tasks;

    public function __construct(string $name, string $prefix)
    {
        $this->id = Uuid::v7();
        $this->ref = new Ref(RefType::Project);
        $this->name = $name;
        $this->prefix = $prefix;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tasks = new ArrayCollection();
    }
}
