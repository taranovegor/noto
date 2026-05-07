<?php

namespace App\Tests\Unit\Factory\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Dto\Attachment\AttachmentDownloadResponseDto;
use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Factory\Attachment\AttachmentDownloadResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class AttachmentDownloadResponseDtoFactoryTest extends TestCase
{
    public function testCreateIncludesDownloadUrl(): void
    {
        $urlGenerator = $this->createMock(ObjectStorage::class);
        $factory = new AttachmentDownloadResponseDtoFactory($urlGenerator);

        $attachment = new Attachment();
        $attachment->originFilename = 'report.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 2048;
        $attachment->path = 'attachments/report.pdf';

        $urlGenerator->expects($this->once())
            ->method('downloadUrl')
            ->with($attachment->path, $attachment->originFilename)
            ->willReturn('https://r2.example.com/download/report.pdf');

        $dto = $factory->create($attachment);

        $this->assertInstanceOf(AttachmentDownloadResponseDto::class, $dto);
        $this->assertEquals($attachment->id, $dto->id);
        $this->assertEquals('report.pdf', $dto->originFilename);
        $this->assertEquals('application/pdf', $dto->mimeType);
        $this->assertEquals(2048, $dto->size);
        $this->assertEquals(AttachmentStatus::Pending, $dto->status);
        $this->assertEquals($attachment->createdAt, $dto->createdAt);
        $this->assertEquals('https://r2.example.com/download/report.pdf', $dto->downloadUrl);
    }
}
