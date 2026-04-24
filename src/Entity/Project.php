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
class Project implements ReferenceableInterface, HasUpdatedAtInterface
{
    use ReferenceableTrait;
    use HasUpdatedAtTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\Column(length: 255)]
    public string $name;

    #[ORM\Column(type: 'ascii_string', length: 3)]
    public string $prefix;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    public private(set) int $taskCounter = 0;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    public array $aliases = [];

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'project')]
    public private(set) Collection $tasks;

    public function __construct(string $name, string $prefix)
    {
        $this->ref = new Ref(RefType::Project);
        $this->id = $this->ref->id;
        $this->name = $name;
        $this->prefix = $prefix;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tasks = new ArrayCollection();
    }
}
