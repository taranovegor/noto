<?php

namespace App\Tests\Unit\Service\Notebook;

use App\Dto\Notebook\CreateNotebookDto;
use App\Dto\Notebook\UpdateNotebookDto;
use App\Entity\Notebook;
use App\Exception\EntityNotFoundException;
use App\Repository\NotebookRepository;
use App\Service\Flusher;
use App\Service\Notebook\NotebookManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NotebookManagerTest extends TestCase
{
    private function makeManager(
        ?NotebookRepository $repo = null,
        ?Flusher $flusher = null,
    ): NotebookManager {
        return new NotebookManager(
            $repo ?? $this->createStub(NotebookRepository::class),
            $flusher ?? $this->createStub(Flusher::class),
        );
    }

    public function testCreateNotebook(): void
    {
        $repo = $this->createMock(NotebookRepository::class);
        $flusher = $this->createMock(Flusher::class);
        $repo->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager($repo, $flusher);
        $dto = new CreateNotebookDto(title: 'Test Title', description: 'Test Description');
        $result = $manager->create($dto);
        $this->assertInstanceOf(Notebook::class, $result);
        $this->assertEquals('Test Title', $result->title);
        $this->assertEquals('Test Description', $result->description);
    }

    public function testGetNotebookReturnsExistingNotebook(): void
    {
        $id = Uuid::v7();
        $notebook = new Notebook('Title', 'Description');
        $repo = $this->createMock(NotebookRepository::class);
        $repo->expects($this->once())->method('find')->with($id)->willReturn($notebook);
        $manager = $this->makeManager($repo);
        $result = $manager->get($id);
        $this->assertSame($notebook, $result);
    }

    public function testGetNotebookThrowsOnNotFound(): void
    {
        $id = Uuid::v7();
        $repo = $this->createMock(NotebookRepository::class);
        $repo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $manager = $this->makeManager($repo);
        $this->expectException(EntityNotFoundException::class);
        $manager->get($id);
    }

    public function testUpdateNotebookUpdatesFields(): void
    {
        $notebook = new Notebook('Old Title', 'Old Description');
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager(flusher: $flusher);
        $dto = new UpdateNotebookDto(title: 'New Title', description: 'New Description');
        $manager->update($notebook, $dto);
        $this->assertEquals('New Title', $notebook->title);
        $this->assertEquals('New Description', $notebook->description);
    }

    public function testUpdateNotebookDoesNotChangeFieldsIfNull(): void
    {
        $notebook = new Notebook('Original Title', 'Original Description');
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');
        $manager = $this->makeManager(flusher: $flusher);
        $dto = new UpdateNotebookDto(title: null, description: null);
        $manager->update($notebook, $dto);
        $this->assertEquals('Original Title', $notebook->title);
        $this->assertEquals('Original Description', $notebook->description);
    }
}
