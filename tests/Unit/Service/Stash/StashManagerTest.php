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

    public function testUpdatePinnedToTrueRemovesExpiration(): void
    {
        $manager = new StashManager(
            $this->createStub(StashRepository::class),
            $this->createStub(AttachmentManager::class),
            $this->createStub(LinkRepository::class),
            $this->createStub(Flusher::class),
        );

        $stash = new Stash(StashType::Text);
        $stash->expiresAt = new \DateTimeImmutable()->modify('+1 day');

        $manager->update($stash, new UpdateStashDto(pinned: true));

        $this->assertNull($stash->expiresAt);
    }

    public function testUpdatePinnedToFalseAddsExpiration(): void
    {
        $ttl = new \DateInterval('P7D');
        $manager = new StashManager(
            $this->createStub(StashRepository::class),
            $this->createStub(AttachmentManager::class),
            $this->createStub(LinkRepository::class),
            $this->createStub(Flusher::class),
            $ttl,
        );

        $stash = new Stash(StashType::Text);
        $stash->pinned = true;
        $stash->expiresAt = null;

        $beforeUpdate = new \DateTimeImmutable();
        $manager->update($stash, new UpdateStashDto(pinned: false));
        $afterUpdate = new \DateTimeImmutable();

        $this->assertNotNull($stash->expiresAt);
        $this->assertGreaterThanOrEqual($beforeUpdate->add($ttl), $stash->expiresAt);
        $this->assertLessThanOrEqual($afterUpdate->add($ttl), $stash->expiresAt);
    }

    public function testUpdatePinnedWithSameValueDoesNotUpdate(): void
    {
        $manager = new StashManager(
            $this->createStub(StashRepository::class),
            $this->createStub(AttachmentManager::class),
            $this->createStub(LinkRepository::class),
            $this->createStub(Flusher::class),
        );

        $stash = new Stash(StashType::Text);
        $originalExpiresAt = $stash->expiresAt;

        $manager->update($stash, new UpdateStashDto(pinned: false));

        $this->assertFalse($stash->pinned);
        $this->assertEquals($originalExpiresAt, $stash->expiresAt);
    }

    public function testUpdatePinnedWithNullValueDoesNotUpdate(): void
    {
        $manager = new StashManager(
            $this->createStub(StashRepository::class),
            $this->createStub(AttachmentManager::class),
            $this->createStub(LinkRepository::class),
            $this->createStub(Flusher::class),
        );

        $stash = new Stash(StashType::Text);
        $originalExpiresAt = $stash->expiresAt;

        $manager->update($stash, new UpdateStashDto(pinned: null));

        $this->assertFalse($stash->pinned);
        $this->assertEquals($originalExpiresAt, $stash->expiresAt);
    }
}
