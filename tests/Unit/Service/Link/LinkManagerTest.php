<?php

namespace App\Tests\Unit\Service\Link;

use App\Dto\Link\CreateLinkDto;
use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Exception\EntityNotFoundException;
use App\Repository\LinkRepository;
use App\Service\Flusher;
use App\Service\Link\LinkManager;
use App\Service\Ref\RefManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class LinkManagerTest extends TestCase
{
    public function testCreatePersistsAndReturnsLink(): void
    {
        $linkRepository = $this->createMock(LinkRepository::class);
        $refManager = $this->createMock(RefManager::class);
        $flusher = $this->createMock(Flusher::class);

        $source = new Ref(RefType::Task);
        $target = new Ref(RefType::Note);

        $dto = new CreateLinkDto($source->id, $target->id, LinkKind::Reference);

        $refManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$dto->sourceId, $source],
                [$dto->targetId, $target],
            ]);

        $manager = new LinkManager($linkRepository, $refManager, $flusher);

        $linkRepository->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');

        $link = $manager->create($dto);

        $this->assertInstanceOf(Link::class, $link);
        $this->assertEquals($source->id, $link->source->id);
        $this->assertEquals($target->id, $link->target->id);
        $this->assertEquals(LinkKind::Reference, $link->kind);
    }

    public function testCreateThrowsWhenSourceEqualsTarget(): void
    {
        $manager = new LinkManager(
            $this->createStub(LinkRepository::class),
            $this->createStub(RefManager::class),
            $this->createStub(Flusher::class),
        );

        $id = Uuid::v7();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot link an entity to itself.');

        $manager->create(new CreateLinkDto($id, $id, LinkKind::Reference));
    }

    public function testCreateThrowsWhenSourceNotFound(): void
    {
        $refManager = $this->createMock(RefManager::class);

        $sourceId = Uuid::v7();
        $targetId = Uuid::v7();

        $refManager->expects($this->once())
            ->method('get')
            ->with($sourceId)
            ->willThrowException(new EntityNotFoundException('Ref', $sourceId));

        $manager = new LinkManager(
            $this->createStub(LinkRepository::class),
            $refManager,
            $this->createStub(Flusher::class),
        );

        $this->expectException(EntityNotFoundException::class);
        $manager->create(new CreateLinkDto($sourceId, $targetId, LinkKind::Ownership));
    }
}
