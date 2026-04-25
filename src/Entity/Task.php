<?php

namespace App\Entity;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\Task\TaskCodeGenerator;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
#[Indexable('id', fields: ['name', 'note'])]
#[ORM\HasLifecycleCallbacks]
class Task implements ReferenceableInterface, HasUpdatedAtInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public private(set) ?Project $project = null;

    #[ORM\Column(unique: true, nullable: true)]
    public private(set) ?string $code = null;

    #[ORM\Column(length: 255)]
    public string $name;

    #[ORM\Column(length: 20, enumType: TaskStatus::class)]
    public TaskStatus $status;

    #[ORM\Column(length: 20, nullable: true, enumType: TaskPriority::class)]
    public ?TaskPriority $priority = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $deadline = null;

    #[ORM\Column(type: 'text')]
    public string $note = '';

    public function __construct(string $name)
    {
        $this->initRef(RefType::Task);
        $this->name = $name;
        $this->status = TaskStatus::Backlog;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    /**
     * @param string $code MUST be unique per each project. Please, use TaskCodeGenerator
     *
     * @see TaskCodeGenerator::generate()
     */
    public function setProject(Project $project, string $code): void
    {
        $this->project = $project;
        $this->code = $code;
    }
}
