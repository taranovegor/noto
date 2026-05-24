<?php

namespace App\Entity;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use App\Repository\NotebookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotebookRepository::class)]
#[ORM\Table(name: 'notebooks')]
#[ORM\HasLifecycleCallbacks]
#[Indexable('id', fields: ['title', 'description'])]
class Notebook implements ReferenceableInterface, HasUpdatedAtInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(type: 'string')]
    public string $title;

    #[ORM\Column(type: 'text')]
    public string $description;

    public function __construct(string $title, string $description)
    {
        $this->initRef();
        $this->title = $title;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    public static function getRefType(): RefType
    {
        return RefType::Notebook;
    }
}
