<?php

namespace App\Entity;

use App\Enum\RefType;
use Doctrine\ORM\Mapping as ORM;

trait ReferenceableTrait
{
    use UidTrait;

    #[ORM\OneToOne(targetEntity: Ref::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $ref;

    public function getRef(): Ref
    {
        return $this->ref;
    }

    private function initRef(RefType $type): void
    {
        $this->ref = new Ref($type);
        $this->id = $this->ref->id;
    }
}
