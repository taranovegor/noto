<?php

namespace App\Tests\Unit\Factory\Notebook;

use App\Entity\Notebook;
use App\Factory\Notebook\NotebookResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class NotebookResponseDtoFactoryTest extends TestCase
{
    public function testCreateConvertsNotebookToDto(): void
    {
        $factory = new NotebookResponseDtoFactory();
        $notebook = new Notebook('Test Title', 'Test Description');
        $dto = $factory->create($notebook);
        $this->assertEquals($notebook->id, $dto->id);
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('Test Description', $dto->description);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $factory = new NotebookResponseDtoFactory();
        $notebook = new Notebook('Title', 'Description');
        $dto = $factory->create($notebook);
        $this->assertEquals($notebook->createdAt, $dto->createdAt);
        $this->assertEquals($notebook->updatedAt, $dto->updatedAt);
    }
}
