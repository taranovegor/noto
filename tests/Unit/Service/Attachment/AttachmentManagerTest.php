<?php

namespace App\Tests\Unit\Service\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Dto\Attachment\AttachmentDto;
use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Repository\AttachmentRepository;
use App\Service\Attachment\AttachmentManager;
use App\Service\Attachment\AttachmentPathGenerator;
use App\Service\Flusher;
use PHPUnit\Framework\TestCase;

class AttachmentManagerTest extends TestCase
{
    private function makeManager(
        AttachmentRepository $repo,
        Flusher $flusher,
        ObjectStorage $storage,
    ): AttachmentManager {
        return new AttachmentManager($repo, $flusher, new AttachmentPathGenerator(), $storage);
    }

    public function testCreatePersistsAndReturnsAttachment(): void
    {
        $attachmentRepository = $this->createMock(AttachmentRepository::class);
        $flusher = $this->createMock(Flusher::class);
        $manager = $this->makeManager($attachmentRepository, $flusher, $this->createStub(ObjectStorage::class));

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

    public function testDeleteRemovesFromStorageAndRepository(): void
    {
        $repo = $this->createMock(AttachmentRepository::class);
        $flusher = $this->createMock(Flusher::class);
        $storage = $this->createMock(ObjectStorage::class);
        $manager = $this->makeManager($repo, $flusher, $storage);

        $attachment = new Attachment();
        $attachment->originFilename = 'file.png';
        $attachment->mimeType = 'image/png';
        $attachment->size = 1024;
        $attachment->path = 'attachments/test.png';

        $storage->expects($this->once())->method('exists')->with('attachments/test.png')->willReturn(true);
        $storage->expects($this->once())->method('delete')->with('attachments/test.png');
        $repo->expects($this->once())->method('remove')->with($attachment);
        $flusher->expects($this->never())->method('flush');

        $manager->delete($attachment);
    }

    public function testDeleteSkipsStorageDeletionWhenFileAbsent(): void
    {
        $repo = $this->createMock(AttachmentRepository::class);
        $storage = $this->createMock(ObjectStorage::class);
        $manager = $this->makeManager($repo, $this->createStub(Flusher::class), $storage);

        $attachment = new Attachment();
        $attachment->originFilename = 'file.png';
        $attachment->mimeType = 'image/png';
        $attachment->size = 1024;
        $attachment->path = 'attachments/gone.png';

        $storage->method('exists')->willReturn(false);
        $storage->expects($this->never())->method('delete');
        $repo->expects($this->once())->method('remove');

        $manager->delete($attachment);
    }
}
