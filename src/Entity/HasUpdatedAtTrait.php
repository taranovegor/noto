<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait HasUpdatedAtTrait
{
    #[ORM\Column]
    public private(set) \DateTimeImmutable $updatedAt;

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
