<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Factory\Task\TaskResponseDtoFactory;
use App\Mcp\Resource\TaskResource;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\Flusher;
use App\Service\Project\ProjectManager;
use App\Service\Task\TaskCodeGenerator;
use App\Service\Task\TaskManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TaskResourceTest extends TestCase
{
    private TaskResource $resource;
    private TaskManager $taskManager;
    private TaskResponseDtoFactory $factory;
    private ContainerInterface $container;
    private NormalizerInterface $normalizer;

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
        $this->container = $this->createStub(ContainerInterface::class);
        $this->normalizer = $this->createStub(NormalizerInterface::class);

        $this->resource = new TaskResource($this->taskManager, $this->factory);
        $this->resource->setContainer($this->container);
    }

    public function testResourceHasTaskManager(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        $this->assertContains('taskManager', $propertyNames);
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
