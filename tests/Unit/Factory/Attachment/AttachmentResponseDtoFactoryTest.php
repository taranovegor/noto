<?php

namespace App\Tests\Unit\Factory\Attachment;

use App\Dto\Attachment\AttachmentResponseDto;
use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class AttachmentResponseDtoFactoryTest extends TestCase
{
    private AttachmentResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new AttachmentResponseDtoFactory();
    }

    public function testCreateConvertsAttachmentToDto(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'photo.jpg';
        $attachment->mimeType = 'image/jpeg';
        $attachment->size = 51200;

        $dto = $this->factory->create($attachment);

        $this->assertInstanceOf(AttachmentResponseDto::class, $dto);
        $this->assertEquals($attachment->id, $dto->id);
        $this->assertEquals('photo.jpg', $dto->originFilename);
        $this->assertEquals('image/jpeg', $dto->mimeType);
        $this->assertEquals(51200, $dto->size);
        $this->assertEquals(AttachmentStatus::Pending, $dto->status);
        $this->assertEquals($attachment->createdAt, $dto->createdAt);
    }
}
