<?php

namespace App\Tests\Unit\Service\Link;

use App\Entity\Link;
use App\Entity\Note;
use App\Entity\Task;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Repository\RefRepository;
use App\Service\Link\LinkerInterface;
use App\Service\Link\LinkResolver;
use App\Service\Link\ReferenceLinkSynchronizer;
use League\CommonMark\Parser\MarkdownParserInterface;
use PHPUnit\Framework\TestCase;

class ReferenceLinkSynchronizerTest extends TestCase
{
    private function makeSynchronizer(
        ?LinkResolver $linkResolver = null,
        ?LinkerInterface $linker = null,
        ?RefRepository $refRepository = null,
    ): ReferenceLinkSynchronizer {
        $parser = $this->createStub(MarkdownParserInterface::class);
        $parser->method('parse')->willReturnCallback(function (string $markdown) {
            $env = new \League\CommonMark\Environment\Environment();
            $env->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());

            return (new \League\CommonMark\Parser\MarkdownParser($env))->parse($markdown);
        });

        return new ReferenceLinkSynchronizer(
            $linkResolver ?? $this->createStub(LinkResolver::class),
            $linker ?? $this->createStub(LinkerInterface::class),
            $refRepository ?? $this->createStub(RefRepository::class),
            $parser,
        );
    }

    public function testSyncRemovesStaleLinksAndCreatesNewOnes(): void
    {
        $note = new Note('# Test');
        $existingTarget = new Task('Old Target');
        $newRef = new \App\Entity\Ref(RefType::Task);

        $markdown = "# Test\n\n[Link](/refs/{$newRef->id->toString()})";
        // Set content after creation since the constructor doesn't take it as markdown with links
        $note->content = $markdown;

        $linker = $this->createMock(LinkerInterface::class);
        $linkResolver = $this->createMock(LinkResolver::class);
        $refRepository = $this->createMock(RefRepository::class);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($note->getRef()), LinkKind::Reference)
            ->willReturn([$existingTarget]);

        // Existing target is not in new markdown — unlink
        $linker->expects($this->once())
            ->method('unlink')
            ->with(
                $this->equalTo($note->getRef()),
                $this->equalTo($existingTarget->getRef()),
                LinkKind::Reference,
            );

        $refRepository->expects($this->once())
            ->method('findByIds')
            ->willReturn([$newRef]);

        $expectedLink = new Link($note->getRef(), $newRef, LinkKind::Reference);
        $linker->expects($this->once())
            ->method('link')
            ->with($this->equalTo($note->getRef()), $this->equalTo($newRef), LinkKind::Reference)
            ->willReturn($expectedLink);

        $result = $this->makeSynchronizer(
            linkResolver: $linkResolver,
            linker: $linker,
            refRepository: $refRepository,
        )->sync($note);

        $this->assertCount(1, $result);
        $this->assertSame($expectedLink, $result[0]);
    }

    public function testSyncKeepsExistingLinks(): void
    {
        $target = new Task('Target');
        $note = new Note('# Test');
        $note->content = "# Test\n\n[Link](/refs/{$target->getRef()->id->toString()})";

        $linker = $this->createMock(LinkerInterface::class);
        $linkResolver = $this->createMock(LinkResolver::class);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->willReturn([$target]);

        // Target already matches — no changes
        $linker->expects($this->never())->method('unlink');
        $linker->expects($this->never())->method('link');

        $result = $this->makeSynchronizer(
            linkResolver: $linkResolver,
            linker: $linker,
        )->sync($note);

        $this->assertSame([], $result);
    }

    public function testSyncSkipsSelfReference(): void
    {
        $note = new Note('# Test');
        $note->content = "# Test\n\n[Self](/refs/{$note->getRef()->id->toString()})";

        $linker = $this->createMock(LinkerInterface::class);
        $linkResolver = $this->createMock(LinkResolver::class);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->willReturn([]);

        $linker->expects($this->never())->method('link');
        $linker->expects($this->never())->method('unlink');

        $result = $this->makeSynchronizer(
            linkResolver: $linkResolver,
            linker: $linker,
        )->sync($note);

        $this->assertSame([], $result);
    }

    public function testSyncNoRefsInContent(): void
    {
        $note = new Note('# Just a note');

        $linker = $this->createMock(LinkerInterface::class);
        $linkResolver = $this->createMock(LinkResolver::class);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->willReturn([]);

        $linker->expects($this->never())->method('link');
        $linker->expects($this->never())->method('unlink');

        $result = $this->makeSynchronizer(
            linkResolver: $linkResolver,
            linker: $linker,
        )->sync($note);

        $this->assertSame([], $result);
    }

    public function testSyncRemovesAllExistingWhenContentCleared(): void
    {
        $note = new Note('# Clean');
        $oldTarget = new Task('Old Target');

        $linker = $this->createMock(LinkerInterface::class);
        $linkResolver = $this->createMock(LinkResolver::class);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->willReturn([$oldTarget]);

        $linker->expects($this->once())
            ->method('unlink')
            ->with(
                $this->equalTo($note->getRef()),
                $this->equalTo($oldTarget->getRef()),
                LinkKind::Reference,
            );

        $linker->expects($this->never())->method('link');

        $result = $this->makeSynchronizer(
            linkResolver: $linkResolver,
            linker: $linker,
        )->sync($note);

        $this->assertSame([], $result);
    }

    public function testSyncSkipsUuidsNotFoundInRepository(): void
    {
        $note = new Note('# Test');
        $missingRefId = \Symfony\Component\Uid\Uuid::v7();
        $note->content = "# Test\n\n[Missing](/refs/{$missingRefId->toString()})";

        $linker = $this->createMock(LinkerInterface::class);
        $linkResolver = $this->createMock(LinkResolver::class);
        $refRepository = $this->createMock(RefRepository::class);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->willReturn([]);

        // findByIds returns empty — UUID not found
        $refRepository->expects($this->once())
            ->method('findByIds')
            ->willReturn([]);

        $linker->expects($this->never())->method('link');
        $linker->expects($this->never())->method('unlink');

        $result = $this->makeSynchronizer(
            linkResolver: $linkResolver,
            linker: $linker,
            refRepository: $refRepository,
        )->sync($note);

        $this->assertSame([], $result);
    }
}
