<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Note\CreateNoteDto;
use PHPUnit\Framework\TestCase;

class CreateNoteDtoTest extends TestCase
{
    public function testCreateNoteDtoWithAllFields(): void
    {
        $dto = new CreateNoteDto(title: 'Test Title', content: 'Test Content');
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Content', $dto->content);
    }

    public function testCreateNoteDtoIsReadonly(): void
    {
        $dto = new CreateNoteDto(title: 'Test', content: 'Content');
        $this->expectException(\Error::class);
        $dto->title = 'Modified';
    }

    public function testCreateNoteDtoWithAttachments(): void
    {
        $attachmentIds = ['id1', 'id2'];
        $dto = new CreateNoteDto(title: 'Test', content: 'Content', attachments: $attachmentIds);
        $this->assertEquals($attachmentIds, $dto->attachments);
    }

    public function testCreateNoteDtoWithoutAttachments(): void
    {
        $dto = new CreateNoteDto(title: 'Test', content: 'Content');
        $this->assertNull($dto->attachments);
    }
}
