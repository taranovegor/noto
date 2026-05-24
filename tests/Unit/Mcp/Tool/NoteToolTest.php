<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Mcp\Tool\NoteTool;
use App\Repository\NotebookRepository;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Note\NoteFinder;
use App\Service\Note\NoteManager;
use App\Service\Notebook\NotebookManager;
use PHPUnit\Framework\TestCase;

class NoteToolTest extends TestCase
{
    private NoteTool $tool;
    private NoteManager $noteManager;
    private NoteFinder $noteFinder;
    private NotebookManager $notebookManager;
    private NoteResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $noteRepo = $this->createStub(NoteRepository::class);
        $notebookRepo = $this->createStub(NotebookRepository::class);
        $linker = $this->createStub(LinkerInterface::class);
        $flusher = $this->createStub(Flusher::class);
        $searcher = $this->createStub(SearcherInterface::class);

        $this->notebookManager = new NotebookManager($notebookRepo, $flusher);
        $this->noteManager = new NoteManager($noteRepo, $linker, $flusher);
        $this->noteFinder = new NoteFinder($searcher);
        $this->factory = $this->createStub(NoteResponseDtoFactory::class);

        $this->tool = new NoteTool($this->noteManager, $this->noteFinder, $this->notebookManager, $this->factory);
    }

    public function testToolHasNoteManager(): void
    {
        $this->assertInstanceOf(NoteManager::class, $this->noteManager);
    }

    public function testToolHasFactory(): void
    {
        $this->assertInstanceOf(NoteResponseDtoFactory::class, $this->factory);
    }

    public function testToolHasNoteFinder(): void
    {
        $this->assertInstanceOf(NoteFinder::class, $this->noteFinder);
    }

    public function testToolHasNotebookManager(): void
    {
        $this->assertInstanceOf(NotebookManager::class, $this->notebookManager);
    }

    public function testToolHasCreateMethod(): void
    {
        $this->assertTrue(method_exists($this->tool, 'create'));
    }

    public function testToolHasUpdateMethod(): void
    {
        $this->assertTrue(method_exists($this->tool, 'update'));
    }

    public function testToolHasSearchMethod(): void
    {
        $this->assertTrue(method_exists($this->tool, 'search'));
    }
}
