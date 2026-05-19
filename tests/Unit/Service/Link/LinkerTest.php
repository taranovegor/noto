<?php

namespace App\Tests\Unit\Service\Link;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Entity\Note;
use App\Enum\LinkKind;
use App\Exception\LinkNotFoundException;
use App\Repository\LinkRepository;
use App\Service\Link\Linker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LinkerTest extends TestCase
{
    private LinkRepository&MockObject $linkRepository;
    private Linker $linker;

    protected function setUp(): void
    {
        $this->linkRepository = $this->createMock(LinkRepository::class);
        $this->linker = new Linker($this->linkRepository, $this->createStub(LoggerInterface::class));
    }

    public function testLinkPersistsCorrectLink(): void
    {
        $note = new Note('# Test');
        $attachment = new Attachment();

        $this->linkRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Link $link) use ($note, $attachment) {
                return $link->source->id->equals($note->ref->id)
                    && $link->target->id->equals($attachment->ref->id)
                    && LinkKind::Ownership === $link->kind;
            }));

        $this->linker->link($note->getRef(), $attachment->getRef(), LinkKind::Ownership);
    }

    public function testUnlinkRemovesExistingLink(): void
    {
        $note = new Note('# Test');
        $attachment = new Attachment();
        $link = new Link($note->ref, $attachment->ref, LinkKind::Ownership);

        $this->linkRepository
            ->expects($this->once())
            ->method('findLink')
            ->with($note->ref, $attachment->ref, LinkKind::Ownership)
            ->willReturn($link);

        $this->linkRepository
            ->expects($this->once())
            ->method('remove')
            ->with($link);

        $this->linker->unlink($note->getRef(), $attachment->getRef(), LinkKind::Ownership);
    }

    public function testUnlinkThrowsWhenLinkNotFound(): void
    {
        $note = new Note('# Test');
        $attachment = new Attachment();

        $this->linkRepository
            ->expects($this->once())
            ->method('findLink')
            ->willReturn(null);

        $this->linkRepository->expects($this->never())->method('remove');

        $this->expectException(LinkNotFoundException::class);

        $this->linker->unlink($note->getRef(), $attachment->getRef(), LinkKind::Ownership);
    }
}
