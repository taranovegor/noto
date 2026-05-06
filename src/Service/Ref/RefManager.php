<?php

namespace App\Service\Ref;

use App\Entity\Ref;
use App\Exception\EntityNotFoundException;
use App\Repository\RefRepository;
use Symfony\Component\Uid\Uuid;

readonly class RefManager
{
    public function __construct(
        private RefRepository $refRepository,
    ) {
    }

    public function get(Uuid $id): Ref
    {
        return $this->refRepository->find($id) ?? throw new EntityNotFoundException(Ref::class, $id);
    }
}
