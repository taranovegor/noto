<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Factory\Notebook\NotebookResponseDtoFactory;
use App\Mcp\Tool\NotebookTool;
use App\Repository\NotebookRepository;
use App\Service\Flusher;
use App\Service\Notebook\NotebookManager;
use PHPUnit\Framework\TestCase;

class NotebookToolTest extends TestCase
{
    private NotebookTool $tool;
    private NotebookManager $notebookManager;
    private NotebookResponseDtoFactory $factory;
    private SearcherInterface $searcher;

    protected function setUp(): void
    {
        $repo = $this->createStub(NotebookRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->notebookManager = new NotebookManager($repo, $flusher);
        $this->factory = $this->createStub(NotebookResponseDtoFactory::class);
        $this->searcher = $this->createStub(SearcherInterface::class);

        $this->tool = new NotebookTool($this->notebookManager, $this->factory, $this->searcher);
    }

    public function testToolHasNotebookManager(): void
    {
        $this->assertInstanceOf(NotebookManager::class, $this->notebookManager);
    }

    public function testToolHasFactory(): void
    {
        $this->assertInstanceOf(NotebookResponseDtoFactory::class, $this->factory);
    }

    public function testToolHasSearcher(): void
    {
        $this->assertInstanceOf(SearcherInterface::class, $this->searcher);
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
