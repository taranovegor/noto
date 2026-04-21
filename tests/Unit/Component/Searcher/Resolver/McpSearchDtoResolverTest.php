<?php

namespace App\Tests\Unit\Component\Searcher\Resolver;

use App\Component\Searcher\Enum\SortDirection;
use App\Component\Searcher\Loader\SearchDefinitionLoader;
use App\Component\Searcher\Model\SortInstruction;
use App\Component\Searcher\Resolver\McpSearchDtoResolver;
use App\Dto\Task\SearchTaskDto;
use App\Service\Task\TaskSearchDefinition;
use Mcp\Schema\Request\CallToolRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class McpSearchDtoResolverTest extends TestCase
{
    private McpSearchDtoResolver $resolver;
    private LoggerInterface $logger;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->validator = $this->createStub(ValidatorInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            [TaskSearchDefinition::class, true],
        ]);
        $container->method('get')->willReturnMap([
            [TaskSearchDefinition::class, new TaskSearchDefinition()],
        ]);

        $definitionLoader = new SearchDefinitionLoader($container);

        $this->resolver = new McpSearchDtoResolver(
            $this->validator,
            $definitionLoader,
            $this->logger
        );
    }

    public function testResolveWithPagination(): void
    {
        $request = new CallToolRequest('search_tasks', [
            'limit' => 50,
            'offset' => 100,
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $this->assertInstanceOf(SearchTaskDto::class, $dto);
        $this->assertEmpty($dto->getFilters());
        $this->assertEmpty($dto->getSorting());
        $this->assertEquals(50, $dto->getPagination()->limit);
        $this->assertEquals(100, $dto->getPagination()->offset);
    }

    public function testResolveWithDefaultPagination(): void
    {
        $request = new CallToolRequest('search_tasks', []);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $this->assertInstanceOf(SearchTaskDto::class, $dto);
        $pagination = $dto->getPagination();
        $this->assertEquals(20, $pagination->limit);
        $this->assertEquals(0, $pagination->offset);
    }

    public function testResolveWithAscendingSorting(): void
    {
        $request = new CallToolRequest('search_tasks', [
            'sort' => 'name',
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $sorting = $dto->getSorting();
        $this->assertCount(1, $sorting);
        /** @var SortInstruction $sort */
        $sort = $sorting[0];
        $this->assertEquals('name', $sort->name);
        $this->assertEquals(SortDirection::ASC, $sort->direction);
    }

    public function testResolveWithDescendingSorting(): void
    {
        $request = new CallToolRequest('search_tasks', [
            'sort' => '-deadline',
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $sorting = $dto->getSorting();
        $this->assertCount(1, $sorting);
        /** @var SortInstruction $sort */
        $sort = $sorting[0];
        $this->assertEquals('deadline', $sort->name);
        $this->assertEquals(SortDirection::DESC, $sort->direction);
    }

    public function testResolveWithMultipleSortFields(): void
    {
        $request = new CallToolRequest('search_tasks', [
            'sort' => '-priority;deadline;name',
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $sorting = $dto->getSorting();
        $this->assertCount(3, $sorting);
        $this->assertEquals('priority', $sorting[0]->name);
        $this->assertEquals(SortDirection::DESC, $sorting[0]->direction);
        $this->assertEquals('deadline', $sorting[1]->name);
        $this->assertEquals(SortDirection::ASC, $sorting[1]->direction);
        $this->assertEquals('name', $sorting[2]->name);
        $this->assertEquals(SortDirection::ASC, $sorting[2]->direction);
    }

    public function testResolveWithFilterAsNestedArray(): void
    {
        $request = new CallToolRequest('search_tasks', [
            'filter' => [
                'status' => 'in:backlog,in_progress,done',
            ],
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $filters = $dto->getFilters();
        $this->assertCount(1, $filters);
        $this->assertEquals('status', $filters[0]->name);
        $this->assertEquals(['backlog', 'in_progress', 'done'], $filters[0]->value);
    }

    public function testResolveWithMultipleConditionsSameField(): void
    {
        $request = new CallToolRequest('search_tasks', [
            'filter' => [
                'status' => 'in:backlog,todo;neq:done',
            ],
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $filters = $dto->getFilters();
        $this->assertCount(2, $filters);
        $this->assertEquals('status', $filters[0]->name);
        $this->assertEquals('status', $filters[1]->name);
    }

    public function testResolveWithValidProjectIdFilter(): void
    {
        $projectId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new CallToolRequest('search_tasks', [
            'filter' => [
                'projectId' => $projectId,
            ],
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $filters = $dto->getFilters();
        $this->assertCount(1, $filters);
        $this->assertEquals('projectId', $filters[0]->name);
        $this->assertEquals($projectId, $filters[0]->value);
    }

    public function testResolveReturnsNullForNonSearchDtoClass(): void
    {
        $request = new CallToolRequest('search_tasks', []);

        $result = $this->resolver->resolve($request, \stdClass::class);

        $this->assertNull($result);
    }

    public function testResolveReturnsNullForNonCallToolRequest(): void
    {
        // Use a different request type (not CallToolRequest)
        $request = $this->createStub(\Mcp\Schema\JsonRpc\Request::class);

        $result = $this->resolver->resolve($request, SearchTaskDto::class);

        $this->assertNull($result);
    }

    public function testResolveWithCompleteQuery(): void
    {
        $projectId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new CallToolRequest('search_tasks', [
            'filter' => [
                'projectId' => $projectId,
                'status' => 'in:backlog,in_progress;neq:done',
            ],
            'sort' => '-id;createdAt',
            'limit' => 25,
            'offset' => 50,
        ]);

        $dto = $this->resolver->resolve($request, SearchTaskDto::class);

        $this->assertInstanceOf(SearchTaskDto::class, $dto);
        $this->assertCount(3, $dto->getFilters());
        $this->assertCount(2, $dto->getSorting());
        $this->assertEquals(25, $dto->getPagination()->limit);
        $this->assertEquals(50, $dto->getPagination()->offset);
    }
}
