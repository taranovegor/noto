<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Component\Searcher\SearcherInterface;
use App\Factory\Project\ProjectResponseDtoFactory;
use App\Mcp\Resource\ProjectResource;
use App\Repository\ProjectRepository;
use App\Service\Flusher;
use App\Service\Project\ProjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProjectResourceTest extends TestCase
{
    private ProjectResource $resource;
    private ProjectManager $projectManager;
    private ProjectResponseDtoFactory $factory;
    private SearcherInterface $searcher;
    private ContainerInterface $container;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $projectRepository = $this->createStub(ProjectRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->projectManager = new ProjectManager($projectRepository, $flusher);
        $this->factory = $this->createStub(ProjectResponseDtoFactory::class);
        $this->searcher = $this->createStub(SearcherInterface::class);
        $this->container = $this->createStub(ContainerInterface::class);
        $this->serializer = $this->createStub(SerializerInterface::class);

        $this->resource = new ProjectResource($this->projectManager, $this->factory, $this->searcher);
        $this->resource->setContainer($this->container);
    }

    public function testResourceHasProjectManager(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('projectManager', $propertyNames);
    }

    public function testResourceHasFactory(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('factory', $propertyNames);
    }

    public function testResourceHasSearcher(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('searcher', $propertyNames);
    }

    public function testResourceHasListMethod(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $this->assertTrue($reflection->hasMethod('list'));

        $method = $reflection->getMethod('list');
        $this->assertTrue($method->isPublic());
    }

    public function testResourceHasGetMethod(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $this->assertTrue($reflection->hasMethod('get'));

        $method = $reflection->getMethod('get');
        $this->assertTrue($method->isPublic());
    }
}
