<?php

namespace App\Entity;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Contract\HasUpdatedAtInterface;
use App\Contract\LinkSourceInterface;
use App\Enum\RefType;
use App\Repository\MemoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MemoRepository::class)]
#[ORM\Table(name: 'memos')]
#[ORM\HasLifecycleCallbacks]
#[Indexable('id', fields: ['content'])]
class Memo implements ReferenceableInterface, HasUpdatedAtInterface, LinkSourceInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(type: 'text')]
    public string $content;

    public function __construct(string $content)
    {
        $this->initRef();
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    public static function getRefType(): RefType
    {
        return RefType::Memo;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
