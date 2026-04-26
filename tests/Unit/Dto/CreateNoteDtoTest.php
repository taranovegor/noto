<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Note\CreateNoteDto;
use PHPUnit\Framework\TestCase;

class CreateNoteDtoTest extends TestCase
{
    public function testCreateNoteDtoWithAllFields(): void
    {
        $dto = new CreateNoteDto(
            title: 'Test Note',
            content: 'Test content',
        );

        $this->assertEquals('Test Note', $dto->title);
        $this->assertEquals('Test content', $dto->content);
    }

    public function testCreateNoteDtoIsReadonly(): void
    {
        $dto = new CreateNoteDto(
            title: 'Note',
            content: 'Content',
        );

        $this->expectException(\Error::class);
        $dto->title = 'Modified';
    }

    public function testCreateNoteDtoWithEmptyStrings(): void
    {
        $dto = new CreateNoteDto(
            title: '',
            content: '',
        );

        $this->assertEquals('', $dto->title);
        $this->assertEquals('', $dto->content);
    }

    public function testCreateNoteDtoWithSpecialCharacters(): void
    {
        $dto = new CreateNoteDto(
            title: 'Note with émojis 🎉',
            content: 'Content with <script> tags & special chars',
        );

        $this->assertEquals('Note with émojis 🎉', $dto->title);
        $this->assertEquals('Content with <script> tags & special chars', $dto->content);
    }
}
