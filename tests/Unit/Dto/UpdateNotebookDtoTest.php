<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Notebook\UpdateNotebookDto;
use PHPUnit\Framework\TestCase;

class UpdateNotebookDtoTest extends TestCase
{
    public function testUpdateNotebookDtoWithAllFields(): void
    {
        $dto = new UpdateNotebookDto(title: 'New Title', description: 'New Description');
        $this->assertEquals('New Title', $dto->title);
        $this->assertEquals('New Description', $dto->description);
    }

    public function testUpdateNotebookDtoWithNullFields(): void
    {
        $dto = new UpdateNotebookDto(title: null, description: null);
        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
    }

    public function testUpdateNotebookDtoFieldsAreNullableByDefault(): void
    {
        $dto = new UpdateNotebookDto();
        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
    }
}
