<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait HasCreatedAtTrait
{
    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;
}
