<?php

namespace App\Entity;

use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'projects')]
#[ORM\HasLifecycleCallbacks]
class Project implements ReferenceableInterface, HasUpdatedAtInterface
{
    use UidTrait;
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

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

    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'project')]
    public private(set) Collection $tasks;

    public function __construct(string $name, string $prefix)
    {
        $this->initRef();
        $this->name = $name;
        $this->prefix = $prefix;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
        $this->tasks = new ArrayCollection();
    }

    public static function getRefType(): RefType
    {
        return RefType::Project;
    }
}
