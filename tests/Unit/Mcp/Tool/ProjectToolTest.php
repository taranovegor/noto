<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Factory\Project\ProjectResponseDtoFactory;
use App\Mcp\Tool\ProjectTool;
use App\Repository\ProjectRepository;
use App\Service\Flusher;
use App\Service\Project\ProjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ProjectToolTest extends TestCase
{
    private ProjectTool $tool;
    private ProjectManager $projectManager;
    private ProjectResponseDtoFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $projectRepository = $this->createStub(ProjectRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->projectManager = new ProjectManager($projectRepository, $flusher);
        $this->factory = $this->createStub(ProjectResponseDtoFactory::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->tool = new ProjectTool($this->projectManager, $this->factory);
        $this->tool->setContainer($this->container);
    }

    public function testToolHasProjectManager(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('projectManager', $propertyNames);
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
}
