<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Factory\Notebook\NotebookResponseDtoFactory;
use App\Mcp\Resource\NotebookResource;
use App\Repository\NotebookRepository;
use App\Service\Flusher;
use App\Service\Notebook\NotebookManager;
use PHPUnit\Framework\TestCase;

class NotebookResourceTest extends TestCase
{
    private NotebookResource $resource;
    private NotebookManager $notebookManager;
    private NotebookResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $repo = $this->createStub(NotebookRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->notebookManager = new NotebookManager($repo, $flusher);
        $this->factory = $this->createStub(NotebookResponseDtoFactory::class);

        $this->resource = new NotebookResource($this->notebookManager, $this->factory);
    }

    public function testResourceHasNotebookManager(): void
    {
        $this->assertInstanceOf(NotebookManager::class, $this->notebookManager);
    }

    public function testResourceHasFactory(): void
    {
        $this->assertInstanceOf(NotebookResponseDtoFactory::class, $this->factory);
    }

    public function testResourceHasGetMethod(): void
    {
        $this->assertTrue(method_exists($this->resource, 'get'));
    }
}
