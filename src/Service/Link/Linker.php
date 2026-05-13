<?php

namespace App\Service\Link;

use App\Entity\Link;
use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use App\Exception\LinkNotFoundException;
use App\Repository\LinkRepository;

final readonly class Linker implements LinkerInterface
{
    public function __construct(
        private LinkRepository $linkRepository,
    ) {
    }

    public function link(ReferenceableInterface $source, ReferenceableInterface $target, LinkKind $kind): void
    {
        $this->linkRepository->add(new Link($source->getRef(), $target->getRef(), $kind));
    }

    public function unlink(ReferenceableInterface $source, ReferenceableInterface $target, LinkKind $kind): void
    {
        $link = $this->linkRepository->findLink($source->getRef(), $target->getRef(), $kind);
        if (null === $link) {
            throw new LinkNotFoundException($source, $target, $kind);
        }

        $this->linkRepository->remove($link);
    }
}
