<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Factory\Note\NoteResponseDtoFactory;
use App\Mcp\Resource\NoteResource;
use App\Repository\NoteRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Note\NoteManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class NoteResourceTest extends TestCase
{
    private NoteResource $resource;
    private NoteManager $noteManager;
    private NoteResponseDtoFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $noteRepository = $this->createStub(NoteRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->noteManager = new NoteManager($noteRepository, $this->createStub(LinkerInterface::class), $flusher);
        $this->factory = $this->createStub(NoteResponseDtoFactory::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->resource = new NoteResource($this->noteManager, $this->factory);
        $this->resource->setContainer($this->container);
    }

    public function testResourceHasNoteManager(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('noteManager', $propertyNames);
    }

    public function testResourceHasFactory(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('factory', $propertyNames);
    }

    public function testResourceHasGetMethod(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $this->assertTrue($reflection->hasMethod('get'));

        $method = $reflection->getMethod('get');
        $this->assertTrue($method->isPublic());
    }
}
