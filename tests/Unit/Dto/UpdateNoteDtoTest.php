<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Note\UpdateNoteDto;
use PHPUnit\Framework\TestCase;

class UpdateNoteDtoTest extends TestCase
{
    public function testUpdateNoteDtoWithAllFields(): void
    {
        $dto = new UpdateNoteDto(title: 'New Title', content: 'New Content');
        $this->assertEquals('New Title', $dto->title);
        $this->assertEquals('New Content', $dto->content);
    }

    public function testUpdateNoteDtoWithNullFields(): void
    {
        $dto = new UpdateNoteDto(title: null, content: null);
        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
    }

    public function testUpdateNoteDtoFieldsAreNullableByDefault(): void
    {
        $dto = new UpdateNoteDto();
        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
    }
}
