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
        $content = '# Test Note' . "\n" . 'Test Content';
        $note = new Note($content);

        $dto = $this->factory->create($note);

        $this->assertInstanceOf(NoteResponseDto::class, $dto);
        $this->assertEquals($note->id, $dto->id);
        $this->assertEquals($content, $dto->content);
        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $note = new Note('Content');

        $dto = $this->factory->create($note);

        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
    }

    public function testCreateWithLongContent(): void
    {
        $longContent = str_repeat('Lorem ipsum ', 100);
        $note = new Note($longContent);

        $dto = $this->factory->create($note);

        $this->assertEquals($longContent, $dto->content);
    }
}
