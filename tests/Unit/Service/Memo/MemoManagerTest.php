<?php

namespace App\Tests\Unit\Service\Memo;

use App\Dto\Memo\AttachMemoAttachmentsDto;
use App\Dto\Memo\CreateMemoDto;
use App\Dto\Memo\UpdateMemoDto;
use App\Entity\Attachment;
use App\Entity\Memo;
use App\Exception\EntityNotFoundException;
use App\Repository\MemoRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Memo\MemoManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class MemoManagerTest extends TestCase
{
    private function makeManager(
        ?MemoRepository $repo = null,
        ?LinkerInterface $linker = null,
        ?Flusher $flusher = null,
    ): MemoManager {
        return new MemoManager(
            $repo ?? $this->createStub(MemoRepository::class),
            $linker ?? $this->createStub(LinkerInterface::class),
            $flusher ?? $this->createStub(Flusher::class),
        );
    }

    public function testCreateMemoWithNoAttachments(): void
    {
        $repo = $this->createMock(MemoRepository::class);
        $linker = $this->createStub(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $repo->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');

        $manager = $this->makeManager($repo, $linker, $flusher);
        $dto = new CreateMemoDto(content: 'Test Content');

        $result = $manager->create($dto);

        $this->assertInstanceOf(Memo::class, $result);
        $this->assertEquals('Test Content', $result->content);
    }

    public function testGetMemoReturnsExistingMemo(): void
    {
        $id = Uuid::v7();
        $memo = new Memo('Test');
        $repo = $this->createMock(MemoRepository::class);

        $repo->expects($this->once())->method('find')->with($id)->willReturn($memo);

        $manager = $this->makeManager($repo);
        $result = $manager->get($id);

        $this->assertSame($memo, $result);
    }

    public function testGetMemoThrowsOnNotFound(): void
    {
        $id = Uuid::v7();
        $repo = $this->createMock(MemoRepository::class);

        $repo->expects($this->once())->method('find')->with($id)->willReturn(null);

        $manager = $this->makeManager($repo);

        $this->expectException(EntityNotFoundException::class);
        $manager->get($id);
    }

    public function testUpdateMemoUpdatesContent(): void
    {
        $memo = new Memo('Old');
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $manager = $this->makeManager(flusher: $flusher);
        $dto = new UpdateMemoDto(content: 'New Content');

        $manager->update($memo, $dto);

        $this->assertEquals('New Content', $memo->content);
    }

    public function testUpdateMemoDoesNotChangeContentIfNull(): void
    {
        $memo = new Memo('Original');
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $manager = $this->makeManager(flusher: $flusher);
        $dto = new UpdateMemoDto(content: null);

        $manager->update($memo, $dto);

        $this->assertEquals('Original', $memo->content);
    }

    public function testAttachMemoCreatesLinks(): void
    {
        $memo = new Memo('Test');
        $attachment = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->once())->method('link');
        $flusher->expects($this->once())->method('flush');

        $manager = $this->makeManager(linker: $linker, flusher: $flusher);
        $dto = new AttachMemoAttachmentsDto(attachments: [$attachment]);

        $manager->attach($memo, $dto);
    }

    public function testDetachMemoRemovesLink(): void
    {
        $memo = new Memo('Test');
        $attachment = new Attachment();
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->once())->method('unlink');
        $flusher->expects($this->once())->method('flush');

        $manager = $this->makeManager(linker: $linker, flusher: $flusher);

        $manager->detach($memo, $attachment);
    }
}
