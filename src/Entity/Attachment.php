<?php

namespace App\Entity;

use App\Enum\AttachmentStatus;
use App\Enum\RefType;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachments')]
class Attachment implements ReferenceableInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;

    #[ORM\Column(length: 255)]
    public string $originFilename;

    #[ORM\Column(length: 255)]
    public string $mimeType;

    #[ORM\Column(type: 'integer')]
    public int $size;

    #[ORM\Column(length: 255)]
    public string $path;

    #[ORM\Column(length: 20)]
    public AttachmentStatus $status;

    public function __construct()
    {
        $this->initRef();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = AttachmentStatus::Pending;
    }

    public static function getRefType(): RefType
    {
        return RefType::Attachment;
    }
}
