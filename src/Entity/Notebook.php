<?php

namespace App\Entity;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Contract\HasExtractionInstructions;
use App\Contract\HasUpdatedAtInterface;
use App\Enum\RefType;
use App\Repository\NotebookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotebookRepository::class)]
#[ORM\Table(name: 'notebooks')]
#[ORM\HasLifecycleCallbacks]
#[Indexable('id', fields: ['title', 'description'])]
class Notebook implements ReferenceableInterface, HasUpdatedAtInterface, HasExtractionInstructions
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(type: 'string')]
    public string $title;

    #[ORM\Column(type: 'text')]
    public string $description;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $extractionInstructions = null;

    public function __construct(string $title, string $description, ?string $extractionInstructions = null)
    {
        $this->initRef();
        $this->title = $title;
        $this->description = $description;
        $this->extractionInstructions = $extractionInstructions;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    public static function getRefType(): RefType
    {
        return RefType::Notebook;
    }

    public function getExtractionInstructions(): ?string
    {
        return $this->extractionInstructions;
    }
}
