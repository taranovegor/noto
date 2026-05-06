<?php

namespace App\Tests\Unit\Service\Stash;

use App\Dto\Attachment\AttachmentDto;
use App\Dto\Stash\CreateStashDto;
use App\Dto\Stash\UpdateStashDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Enum\AttachmentStatus;
use App\Enum\StashType;
use App\Repository\LinkRepository;
use App\Repository\StashRepository;
use App\Service\Attachment\AttachmentManager;
use App\Service\Flusher;
use App\Service\Stash\StashManager;
use PHPUnit\Framework\TestCase;

class StashManagerTest extends TestCase
{
    public function testCreateTextStash(): void
    {
        $stashRepository = $this->createMock(StashRepository::class);
        $attachmentManager = $this->createStub(AttachmentManager::class);
        $linkRepository = $this->createStub(LinkRepository::class);
        $flusher = $this->createMock(Flusher::class);

        $manager = new StashManager($stashRepository, $attachmentManager, $linkRepository, $flusher);

        $dto = new CreateStashDto(type: StashType::Text, content: 'hello');

        $stashRepository->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');

        $stash = $manager->create($dto);

        $this->assertInstanceOf(Stash::class, $stash);
        $this->assertEquals(StashType::Text, $stash->type);
        $this->assertEquals('hello', $stash->content);
        $this->assertNotNull($stash->expiresAt);
    }

    public function testCreateFileStashCreatesAttachmentsAndLinks(): void
    {
        $stashRepository = $this->createMock(StashRepository::class);
        $attachmentManager = $this->createMock(AttachmentManager::class);
        $linkRepository = $this->createMock(LinkRepository::class);
        $flusher = $this->createMock(Flusher::class);

        $manager = new StashManager($stashRepository, $attachmentManager, $linkRepository, $flusher);

        $attachmentDto = new AttachmentDto('file.pdf', 'application/pdf', 1024);
        $dto = new CreateStashDto(type: StashType::File, attachments: [$attachmentDto]);

        $attachment = new Attachment();
        $attachment->originFilename = 'file.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 1024;
        $attachment->path = 'attachments/uuid.pdf';
        $attachment->status = AttachmentStatus::Pending;

        $stashRepository->expects($this->once())->method('add');
        $attachmentManager->expects($this->once())
            ->method('create')
            ->with($attachmentDto)
            ->willReturn($attachment);
        $linkRepository->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');

        $stash = $manager->create($dto);

        $this->assertEquals(StashType::File, $stash->type);
    }

    public function testUpdatePinned(): void
    {
        $manager = new StashManager(
            $this->createStub(StashRepository::class),
            $this->createStub(AttachmentManager::class),
            $this->createStub(LinkRepository::class),
            $flusher = $this->createMock(Flusher::class),
        );

        $stash = new Stash(StashType::Text);
        $this->assertFalse($stash->pinned);

        $flusher->expects($this->once())->method('flush');

        $manager->update($stash, new UpdateStashDto(pinned: true));

        $this->assertTrue($stash->pinned);
    }
}
