<?php

namespace App\Service\Link;

use App\Dto\Link\CreateLinkDto;
use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Service\Flusher;
use App\Service\Ref\RefManager;

final readonly class LinkManager
{
    public function __construct(
        private LinkRepository $linkRepository,
        private RefManager $refManager,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateLinkDto $dto): Link
    {
        if ($dto->sourceId->equals($dto->targetId)) {
            throw new \InvalidArgumentException('Cannot link an entity to itself.');
        }

        $source = $this->refManager->get($dto->sourceId);
        $target = $this->refManager->get($dto->targetId);

        $link = new Link($source, $target, $dto->kind);

        $this->linkRepository->add($link);
        $this->flusher->flush();

        return $link;
    }
}
