<?php

namespace App\Tests\Unit\Service\Attachment;

use App\Dto\Attachment\AttachmentDto;
use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Repository\AttachmentRepository;
use App\Service\Attachment\AttachmentManager;
use App\Service\Attachment\AttachmentPathGenerator;
use App\Service\Attachment\AttachmentUrlGenerator;
use App\Service\Flusher;
use PHPUnit\Framework\TestCase;

class AttachmentManagerTest extends TestCase
{
    public function testCreatePersistsAndReturnsAttachment(): void
    {
        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $flusher = $this->createMock(Flusher::class);
        $pathGenerator = new AttachmentPathGenerator();
        $urlGenerator = $this->createStub(AttachmentUrlGenerator::class);

        $manager = new AttachmentManager($attachmentRepository, $flusher, $pathGenerator, $urlGenerator);

        $dto = new AttachmentDto(
            originFilename: 'report.pdf',
            mimeType: 'application/pdf',
            size: 2048,
        );

        $attachmentRepository->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');

        $attachment = $manager->create($dto);

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertEquals('report.pdf', $attachment->originFilename);
        $this->assertEquals('application/pdf', $attachment->mimeType);
        $this->assertEquals(2048, $attachment->size);
        $this->assertEquals(AttachmentStatus::Pending, $attachment->status);
        $this->assertStringStartsWith('attachments/', $attachment->path);
        $this->assertStringEndsWith('.pdf', $attachment->path);
    }
}
