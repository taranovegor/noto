<?php

namespace App\Tests\Unit\Factory\Stash;

use App\Dto\Stash\StashResponseDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Enum\AttachmentStatus;
use App\Enum\StashType;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Attachment\AttachmentUploadResponseDtoFactory;
use App\Factory\Stash\StashResponseDtoFactory;
use App\Service\Attachment\AttachmentManager;
use App\Service\Attachment\AttachmentUrlGenerator;
use PHPUnit\Framework\TestCase;

class StashResponseDtoFactoryTest extends TestCase
{
    public function testCreateReturnsAttachmentsWithoutUploadUrls(): void
    {
        $attachmentManager = $this->createMock(AttachmentManager::class);
        $factory = new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            new AttachmentUploadResponseDtoFactory($this->createStub(AttachmentUrlGenerator::class)),
            $attachmentManager,
        );

        $attachment = new Attachment();
        $attachment->originFilename = 'doc.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 512;

        $stash = new Stash(StashType::File);

        $attachmentManager->expects($this->once())
            ->method('getOwnedBy')
            ->with($stash->ref)
            ->willReturn([$attachment]);

        $dto = $factory->create($stash);

        $this->assertInstanceOf(StashResponseDto::class, $dto);
        $this->assertCount(1, $dto->attachments);
        $this->assertEquals($attachment->id, $dto->attachments[0]->id);
        $this->assertEquals(AttachmentStatus::Pending, $dto->attachments[0]->status);
    }

    public function testCreateWithUploadUrlsIncludesUploadUrl(): void
    {
        $urlGenerator = $this->createMock(AttachmentUrlGenerator::class);
        $attachmentManager = $this->createMock(AttachmentManager::class);

        $factory = new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            new AttachmentUploadResponseDtoFactory($urlGenerator),
            $attachmentManager,
        );

        $attachment = new Attachment();
        $attachment->originFilename = 'doc.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 512;

        $stash = new Stash(StashType::File);

        $attachmentManager->expects($this->once())
            ->method('getOwnedBy')
            ->with($stash->ref)
            ->willReturn([$attachment]);

        $urlGenerator->expects($this->once())
            ->method('generateUploadUrl')
            ->willReturn('https://r2.example.com/upload');

        $dto = $factory->createWithUploadUrls($stash);

        $this->assertCount(1, $dto->attachments);
        $this->assertEquals('https://r2.example.com/upload', $dto->attachments[0]->uploadUrl);
    }

    public function testCreateReturnsNullAttachmentsWhenEmpty(): void
    {
        $attachmentManager = $this->createMock(AttachmentManager::class);
        $factory = new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            new AttachmentUploadResponseDtoFactory($this->createStub(AttachmentUrlGenerator::class)),
            $attachmentManager,
        );

        $stash = new Stash(StashType::Text);

        $attachmentManager->expects($this->once())
            ->method('getOwnedBy')
            ->willReturn([]);

        $dto = $factory->create($stash);

        $this->assertNull($dto->attachments);
    }
}
