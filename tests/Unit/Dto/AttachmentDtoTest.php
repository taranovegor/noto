<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Attachment\AttachmentDto;
use PHPUnit\Framework\TestCase;

class AttachmentDtoTest extends TestCase
{
    public function testCreateDto(): void
    {
        $dto = new AttachmentDto(
            originFilename: 'file.pdf',
            mimeType: 'application/pdf',
            size: 2048,
        );

        $this->assertEquals('file.pdf', $dto->originFilename);
        $this->assertEquals('application/pdf', $dto->mimeType);
        $this->assertEquals(2048, $dto->size);
    }

    public function testDtoIsReadonly(): void
    {
        $dto = new AttachmentDto(
            originFilename: 'file.pdf',
            mimeType: 'application/pdf',
            size: 2048,
        );

        $this->expectException(\Error::class);
        $dto->originFilename = 'other.pdf';
    }
}
