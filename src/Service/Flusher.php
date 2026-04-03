<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

readonly class Flusher
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
