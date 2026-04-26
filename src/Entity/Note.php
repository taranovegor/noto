<?php

namespace App\Entity;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'notes')]
#[ORM\HasLifecycleCallbacks]
#[Indexable('id', fields: ['title', 'content'])]
class Note implements ReferenceableInterface, HasUpdatedAtInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(type: 'string', length: 255)]
    public string $title;

    #[ORM\Column(type: 'text')]
    public string $content;

    public function __construct(string $title, string $content)
    {
        $this->initRef(RefType::Note);
        $this->title = $title;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }
}
