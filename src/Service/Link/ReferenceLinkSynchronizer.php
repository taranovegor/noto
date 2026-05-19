<?php

namespace App\Service\Link;

use App\Contract\LinkSourceInterface;
use App\Entity\Link;
use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use App\Repository\RefRepository;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link as LinkNode;
use League\CommonMark\Parser\MarkdownParserInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ReferenceLinkSynchronizer
{
    public function __construct(
        private LinkResolver $linkResolver,
        private LinkerInterface $linker,
        private RefRepository $refRepository,
        private MarkdownParserInterface $parser,
    ) {
    }

    /**
     * Extracts /refs/<uuid> links from markdown content and syncs them as Reference links.
     *
     * @return Link[]
     *
     * @throws CommonMarkException
     */
    public function sync(LinkSourceInterface&ReferenceableInterface $source): array
    {
        $newUuids = $this->extractRefUuids($source->getContent());
        $sourceRefId = $source->getRef()->id->toString();

        $toAdd = [];
        foreach ($newUuids as $uuid) {
            $uuidStr = $uuid->toString();
            if ($uuidStr !== $sourceRefId) {
                $toAdd[$uuidStr] = $uuid;
            }
        }

        foreach ($this->linkResolver->resolve($source->getRef(), LinkKind::Reference) as $target) {
            $targetId = $target->getRef()->id->toString();
            if (!isset($toAdd[$targetId])) {
                $this->linker->unlink($source->getRef(), $target->getRef(), LinkKind::Reference);
            } else {
                unset($toAdd[$targetId]);
            }
        }

        if (!$toAdd) {
            return [];
        }

        $newLinks = [];
        foreach ($this->refRepository->findByIds(array_values($toAdd)) as $ref) {
            $newLinks[] = $this->linker->link($source->getRef(), $ref, LinkKind::Reference);
        }

        return $newLinks;
    }

    /**
     * @return Uuid[]
     *
     * @throws CommonMarkException
     */
    private function extractRefUuids(string $markdown): array
    {
        $document = $this->parser->parse($markdown);

        $uuids = [];
        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if (!$node instanceof LinkNode || !$event->isEntering()) {
                continue;
            }

            $url = $node->getUrl();
            if (!str_starts_with($url, '/refs/')) {
                continue;
            }

            $uuidStr = substr($url, \strlen('/refs/'));
            if (Uuid::isValid($uuidStr)) {
                $uuids[$uuidStr] = Uuid::fromString($uuidStr);
            }
        }

        return array_values($uuids);
    }
}
