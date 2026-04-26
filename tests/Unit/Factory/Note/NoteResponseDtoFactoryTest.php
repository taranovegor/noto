<?php

namespace App\Tests\Unit\Factory\Note;

use App\Dto\Note\NoteResponseDto;
use App\Entity\Note;
use App\Factory\Note\NoteResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class NoteResponseDtoFactoryTest extends TestCase
{
    private NoteResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new NoteResponseDtoFactory();
    }

    public function testCreateConvertsNoteToDto(): void
    {
        $note = new Note('Test Note', 'Test Content');

        $dto = $this->factory->create($note);

        $this->assertInstanceOf(NoteResponseDto::class, $dto);
        $this->assertEquals($note->id, $dto->id);
        $this->assertEquals('Test Note', $dto->title);
        $this->assertEquals('Test Content', $dto->content);
        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $note = new Note('Title', 'Content');

        $dto = $this->factory->create($note);

        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
    }

    public function testCreateWithLongContent(): void
    {
        $longContent = str_repeat('Lorem ipsum ', 100);
        $note = new Note('Note with long content', $longContent);

        $dto = $this->factory->create($note);

        $this->assertEquals($longContent, $dto->content);
    }
}
