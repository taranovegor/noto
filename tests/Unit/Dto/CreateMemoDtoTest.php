<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Memo\CreateMemoDto;
use PHPUnit\Framework\TestCase;

class CreateMemoDtoTest extends TestCase
{
    public function testCreateMemoDtoWithAllFields(): void
    {
        $dto = new CreateMemoDto(
            content: 'Test content',
        );

        $this->assertEquals('Test content', $dto->content);
    }

    public function testCreateMemoDtoIsReadonly(): void
    {
        $dto = new CreateMemoDto(content: 'Test');

        $this->expectException(\Error::class);
        $dto->content = 'Modified';
    }

    public function testCreateMemoDtoWithAttachments(): void
    {
        $attachmentIds = ['id1', 'id2'];
        $dto = new CreateMemoDto(
            content: 'Test content',
            attachments: $attachmentIds,
        );

        $this->assertEquals($attachmentIds, $dto->attachments);
    }

    public function testCreateMemoDtoWithoutAttachments(): void
    {
        $dto = new CreateMemoDto(content: 'Test content');

        $this->assertNull($dto->attachments);
    }
}
