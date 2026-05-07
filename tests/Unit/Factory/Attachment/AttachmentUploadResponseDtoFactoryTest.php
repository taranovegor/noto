<?php

namespace App\Tests\Unit\Factory\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Dto\Attachment\AttachmentUploadResponseDto;
use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Factory\Attachment\AttachmentUploadResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class AttachmentUploadResponseDtoFactoryTest extends TestCase
{
    public function testCreateIncludesUploadUrl(): void
    {
        $urlGenerator = $this->createMock(ObjectStorage::class);
        $factory = new AttachmentUploadResponseDtoFactory($urlGenerator);

        $attachment = new Attachment();
        $attachment->originFilename = 'doc.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 1024;
        $attachment->path = 'attachments/doc.pdf';

        $urlGenerator->expects($this->once())
            ->method('uploadUrl')
            ->with($attachment->path, $attachment->mimeType, $attachment->size)
            ->willReturn('https://r2.example.com/upload-url');

        $dto = $factory->create($attachment);

        $this->assertInstanceOf(AttachmentUploadResponseDto::class, $dto);
        $this->assertEquals($attachment->id, $dto->id);
        $this->assertEquals('doc.pdf', $dto->originFilename);
        $this->assertEquals('application/pdf', $dto->mimeType);
        $this->assertEquals(1024, $dto->size);
        $this->assertEquals(AttachmentStatus::Pending, $dto->status);
        $this->assertEquals($attachment->createdAt, $dto->createdAt);
        $this->assertEquals('https://r2.example.com/upload-url', $dto->uploadUrl);
    }
}
