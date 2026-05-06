<?php

namespace App\Tests\Unit\Factory\Stash;

use App\Dto\Stash\StashResponseDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Enum\AttachmentStatus;
use App\Enum\LinkKind;
use App\Enum\StashType;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Attachment\AttachmentUploadResponseDtoFactory;
use App\Factory\Stash\StashResponseDtoFactory;
use App\Service\Attachment\AttachmentUrlGenerator;
use App\Service\Link\LinkResolver;
use PHPUnit\Framework\TestCase;

class StashResponseDtoFactoryTest extends TestCase
{
    public function testCreateReturnsAttachmentsWithoutUploadUrls(): void
    {
        $linkResolver = $this->createMock(LinkResolver::class);
        $factory = new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            new AttachmentUploadResponseDtoFactory($this->createStub(AttachmentUrlGenerator::class)),
            $linkResolver,
        );

        $attachment = new Attachment();
        $attachment->originFilename = 'doc.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 512;

        $stash = new Stash(StashType::File);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->with($stash, LinkKind::Ownership, Attachment::class)
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
        $linkResolver = $this->createMock(LinkResolver::class);

        $factory = new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            new AttachmentUploadResponseDtoFactory($urlGenerator),
            $linkResolver,
        );

        $attachment = new Attachment();
        $attachment->originFilename = 'doc.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 512;

        $stash = new Stash(StashType::File);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->with($stash, LinkKind::Ownership, Attachment::class)
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
        $linkResolver = $this->createMock(LinkResolver::class);
        $factory = new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            new AttachmentUploadResponseDtoFactory($this->createStub(AttachmentUrlGenerator::class)),
            $linkResolver,
        );

        $stash = new Stash(StashType::Text);

        $linkResolver->expects($this->once())
            ->method('resolve')
            ->willReturn([]);

        $dto = $factory->create($stash);

        $this->assertNull($dto->attachments);
    }
}
