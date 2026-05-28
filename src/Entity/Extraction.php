<?php

namespace App\Entity;

use App\Contract\HasUpdatedAtInterface;
use App\Dto\Extraction\Fragment;
use App\Enum\ExtractionStatus;
use App\Enum\RefType;
use App\Repository\ExtractionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtractionRepository::class)]
#[ORM\Table(name: 'extractions')]
class Extraction implements ReferenceableInterface, HasUpdatedAtInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(length: 32, enumType: ExtractionStatus::class)]
    public ExtractionStatus $status;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $prompt;

    #[ORM\Column(length: 50, enumType: RefType::class)]
    public RefType $targetType;

    #[ORM\ManyToOne(targetEntity: Ref::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public private(set) ?Ref $targetParent;

    /**
     * @var list<array{type: string, id: string, storageKey: ?string, mimeType: ?string, filename: ?string, status: string, result: ?string, error: ?string}>|null
     */
    #[ORM\Column(type: 'json', nullable: true, options: ['jsonb' => true])]
    public private(set) ?array $fragments = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $errorMessage = null;

    public function __construct(RefType $targetType, ?Ref $targetParent = null, ?string $prompt = null)
    {
        $this->initRef();
        $this->status = ExtractionStatus::Pending;
        $this->targetType = $targetType;
        $this->targetParent = $targetParent;
        $this->prompt = $prompt;
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    /**
     * @return Fragment[]
     */
    public function getFragments(): array
    {
        return array_map(static fn (array $data) => Fragment::fromArray($data), $this->fragments ?? []);
    }

    /**
     * @param Fragment[] $fragments
     */
    public function setFragments(array $fragments): void
    {
        $this->fragments = array_map(static fn (Fragment $f) => $f->jsonSerialize(), $fragments);
    }

    public static function getRefType(): RefType
    {
        return RefType::Extraction;
    }
}
