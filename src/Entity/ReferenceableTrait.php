<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ReferenceableTrait
{
    #[ORM\OneToOne(targetEntity: Ref::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) Ref $ref;

    public function getRef(): Ref
    {
        return $this->ref;
    }
}
