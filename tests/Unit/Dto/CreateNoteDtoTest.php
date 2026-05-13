<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Note\CreateNoteDto;
use PHPUnit\Framework\TestCase;

class CreateNoteDtoTest extends TestCase
{
    public function testCreateNoteDtoWithAllFields(): void
    {
        $dto = new CreateNoteDto(
            content: 'Test content',
        );

        $this->assertEquals('Test content', $dto->content);
    }

    public function testCreateNoteDtoIsReadonly(): void
    {
        $dto = new CreateNoteDto(
            content: 'Content',
        );

        $this->expectException(\Error::class);
        $dto->content = 'Modified';
    }

    public function testCreateNoteDtoWithEmptyContent(): void
    {
        $dto = new CreateNoteDto(
            content: '',
        );

        $this->assertEquals('', $dto->content);
    }

    public function testCreateNoteDtoWithSpecialCharacters(): void
    {
        $dto = new CreateNoteDto(
            content: 'Content with <script> tags & special chars',
        );

        $this->assertEquals('Content with <script> tags & special chars', $dto->content);
    }
}
