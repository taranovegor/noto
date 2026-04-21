<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Factory\Task\TaskResponseDtoFactory;
use App\Mcp\Tool\TaskTool;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\Flusher;
use App\Service\Project\ProjectManager;
use App\Service\Task\TaskCodeGenerator;
use App\Service\Task\TaskManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TaskToolTest extends TestCase
{
    private TaskTool $tool;
    private TaskManager $taskManager;
    private TaskResponseDtoFactory $factory;
    private SearcherInterface $searcher;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $taskRepository = $this->createStub(TaskRepository::class);
        $projectRepository = $this->createStub(ProjectRepository::class);
        $projectFlusher = $this->createStub(Flusher::class);
        $projectManager = new ProjectManager($projectRepository, $projectFlusher);

        $codeGenerator = $this->createStub(TaskCodeGenerator::class);
        $flusher = $this->createStub(Flusher::class);

        $this->taskManager = new TaskManager(
            $taskRepository,
            $projectManager,
            $codeGenerator,
            $flusher,
        );

        $this->factory = $this->createStub(TaskResponseDtoFactory::class);
        $this->searcher = $this->createStub(SearcherInterface::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->tool = new TaskTool($this->taskManager, $this->factory, $this->searcher);
        $this->tool->setContainer($this->container);
    }

    public function testToolHasTaskManager(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('taskManager', $propertyNames);
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
