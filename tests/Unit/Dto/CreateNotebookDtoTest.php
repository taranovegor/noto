<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Notebook\CreateNotebookDto;
use PHPUnit\Framework\TestCase;

class CreateNotebookDtoTest extends TestCase
{
    public function testCreateNotebookDtoWithAllFields(): void
    {
        $dto = new CreateNotebookDto(title: 'Test Title', description: 'Test Description');
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Description', $dto->description);
    }

    public function testCreateNotebookDtoIsReadonly(): void
    {
        $dto = new CreateNotebookDto(title: 'Test', description: 'Description');
        $this->expectException(\Error::class);
        $dto->title = 'Modified';
    }
}
