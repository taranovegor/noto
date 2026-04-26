<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Mcp\Tool\NoteTool;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Note\NoteManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class NoteToolTest extends TestCase
{
    private NoteTool $tool;
    private NoteManager $noteManager;
    private NoteResponseDtoFactory $factory;
    private SearcherInterface $searcher;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $noteRepository = $this->createStub(NoteRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->noteManager = new NoteManager($noteRepository, $flusher);
        $this->factory = $this->createStub(NoteResponseDtoFactory::class);
        $this->searcher = $this->createStub(SearcherInterface::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->tool = new NoteTool($this->noteManager, $this->factory, $this->searcher);
        $this->tool->setContainer($this->container);
    }

    public function testToolHasNoteManager(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('noteManager', $propertyNames);
    }

    public function testToolHasFactory(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('factory', $propertyNames);
    }

    public function testToolHasCreateMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('create'));

        $method = $reflection->getMethod('create');
        $this->assertTrue($method->isPublic());
    }

    public function testToolHasUpdateMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('update'));

        $method = $reflection->getMethod('update');
        $this->assertTrue($method->isPublic());
    }

    public function testToolHasSearchMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('search'));

        $method = $reflection->getMethod('search');
        $this->assertTrue($method->isPublic());
    }

    public function testToolHasSearcher(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('searcher', $propertyNames);
    }
}
