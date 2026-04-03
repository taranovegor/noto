<?php

namespace App\Entity;

use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Service\Task\TaskCodeGenerator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
class Task implements HasUpdatedAtInterface
{
    use HasUpdatedAtTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\OneToOne(targetEntity: Ref::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $ref;

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

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(string $name)
    {
        $this->id = Uuid::v7();
        $this->ref = new Ref(RefType::Task);
        $this->name = $name;
        $this->status = TaskStatus::Backlog;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
