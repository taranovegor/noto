<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Factory\Note\NoteResponseDtoFactory;
use App\Mcp\Resource\NoteResource;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Note\NoteManager;
use PHPUnit\Framework\TestCase;

class NoteResourceTest extends TestCase
{
    private NoteResource $resource;
    private NoteManager $noteManager;
    private NoteResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $repo = $this->createStub(NoteRepository::class);
        $linker = $this->createStub(LinkerInterface::class);
        $flusher = $this->createStub(Flusher::class);

        $this->noteManager = new NoteManager($repo, $linker, $flusher);
        $this->factory = $this->createStub(NoteResponseDtoFactory::class);

        $this->resource = new NoteResource($this->noteManager, $this->factory);
    }

    public function testResourceHasNoteManager(): void
    {
        $this->assertInstanceOf(NoteManager::class, $this->noteManager);
    }

    public function testResourceHasFactory(): void
    {
        $this->assertInstanceOf(NoteResponseDtoFactory::class, $this->factory);
    }

    public function testResourceHasGetMethod(): void
    {
        $this->assertTrue(method_exists($this->resource, 'get'));
    }
}
