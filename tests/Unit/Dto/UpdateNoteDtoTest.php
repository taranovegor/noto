<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Note\UpdateNoteDto;
use PHPUnit\Framework\TestCase;

class UpdateNoteDtoTest extends TestCase
{
    public function testUpdateNoteDtoWithContent(): void
    {
        $dto = new UpdateNoteDto(
            content: 'Updated Content',
        );

        $this->assertEquals('Updated Content', $dto->content);
    }

    public function testUpdateNoteDtoWithNullContent(): void
    {
        $dto = new UpdateNoteDto(
            content: null,
        );

        $this->assertNull($dto->content);
    }

    public function testUpdateNoteDtoIsReadonly(): void
    {
        $dto = new UpdateNoteDto();

        $this->expectException(\Error::class);
        $dto->content = 'Modified';
    }
}
