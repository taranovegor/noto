<?php

namespace App\Service\Link;

use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;
use App\Exception\LinkNotFoundException;
use App\Repository\LinkRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class Linker implements LinkerInterface
{
    public function __construct(
        private LinkRepository $linkRepository,
        #[Autowire('@monolog.logger.link')]
        private LoggerInterface $logger,
    ) {
    }

    public function link(Ref $source, Ref $target, LinkKind $kind): Link
    {
        $link = new Link($source, $target, $kind);
        $this->linkRepository->add($link);

        $this->logger->debug('Link created', [
            'linkId' => $link->id->toString(),
            'source' => $source->id->toString(),
            'sourceType' => $source->type->value,
            'target' => $target->id->toString(),
            'targetType' => $target->type->value,
            'kind' => $kind->value,
        ]);

        return $link;
    }

    public function unlink(Ref $source, Ref $target, LinkKind $kind): void
    {
        $link = $this->linkRepository->findLink($source, $target, $kind);
        if (null === $link) {
            throw new LinkNotFoundException($source, $target, $kind);
        }

        $this->linkRepository->remove($link);

        $this->logger->debug('Link removed', [
            'linkId' => $link->id->toString(),
            'source' => $source->id->toString(),
            'sourceType' => $source->type->value,
            'target' => $target->id->toString(),
            'targetType' => $target->type->value,
            'kind' => $kind->value,
        ]);
    }
}
