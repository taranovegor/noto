<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Note\UpdateNoteDto;
use PHPUnit\Framework\TestCase;

class UpdateNoteDtoTest extends TestCase
{
    public function testUpdateNoteDtoWithAllFields(): void
    {
        $dto = new UpdateNoteDto(
            title: 'Updated Title',
            content: 'Updated Content',
        );

        $this->assertEquals('Updated Title', $dto->title);
        $this->assertEquals('Updated Content', $dto->content);
    }

    public function testUpdateNoteDtoWithNullFields(): void
    {
        $dto = new UpdateNoteDto(
            title: null,
            content: null,
        );

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
    }

    public function testUpdateNoteDtoWithPartialUpdate(): void
    {
        $dto = new UpdateNoteDto(
            title: 'New Title',
            content: null,
        );

        $this->assertEquals('New Title', $dto->title);
        $this->assertNull($dto->content);
    }

    public function testUpdateNoteDtoIsReadonly(): void
    {
        $dto = new UpdateNoteDto();

        $this->expectException(\Error::class);
        $dto->title = 'Modified';
    }
}
