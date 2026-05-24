<?php

namespace App\Entity;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Contract\HasUpdatedAtInterface;
use App\Contract\LinkSourceInterface;
use App\Enum\RefType;
use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'notes')]
#[ORM\HasLifecycleCallbacks]
#[Indexable('id', fields: ['title', 'content'])]
class Note implements ReferenceableInterface, HasUpdatedAtInterface, LinkSourceInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\ManyToOne(targetEntity: Notebook::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Notebook $notebook;

    #[ORM\Column(type: 'string')]
    public string $title;

    #[ORM\Column(type: 'text')]
    public string $content;

    public function __construct(Notebook $notebook, string $title, string $content)
    {
        $this->initRef();
        $this->notebook = $notebook;
        $this->title = $title;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    public static function getRefType(): RefType
    {
        return RefType::Note;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
