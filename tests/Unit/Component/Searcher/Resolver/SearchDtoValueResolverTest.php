<?php

namespace App\Tests\Unit\Component\Searcher\Resolver;

use App\Component\Searcher\Enum\SortDirection;
use App\Component\Searcher\Loader\SearchDefinitionLoader;
use App\Component\Searcher\Model\SortInstruction;
use App\Component\Searcher\Resolver\SearchDtoValueResolver;
use App\Dto\Task\SearchTaskDto;
use App\Service\Task\TaskSearchDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SearchDtoValueResolverTest extends TestCase
{
    private SearchDtoValueResolver $resolver;
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

        $this->resolver = new SearchDtoValueResolver(
            $this->validator,
            $definitionLoader,
            $this->logger
        );
    }

    public function testResolveWithoutFiltersOrSorting(): void
    {
        $request = Request::create('/?limit=10&offset=0');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SearchTaskDto::class, $result[0]);
        $this->assertEmpty($result[0]->getFilters());
        $this->assertEmpty($result[0]->getSorting());
        $this->assertEquals(10, $result[0]->getPagination()->limit);
        $this->assertEquals(0, $result[0]->getPagination()->offset);
    }

    public function testResolveWithAscendingSorting(): void
    {
        $request = Request::create('/?sort=name');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $sorting = $dto->getSorting();

        $this->assertCount(1, $sorting);
        /** @var SortInstruction $sort */
        $sort = $sorting[0];
        $this->assertEquals('name', $sort->name);
        $this->assertEquals(SortDirection::ASC, $sort->direction);
    }

    public function testResolveWithDescendingSorting(): void
    {
        $request = Request::create('/?sort=-deadline');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $sorting = $dto->getSorting();

        $this->assertCount(1, $sorting);
        /** @var SortInstruction $sort */
        $sort = $sorting[0];
        $this->assertEquals('deadline', $sort->name);
        $this->assertEquals(SortDirection::DESC, $sort->direction);
    }

    public function testResolveWithMultipleSortFields(): void
    {
        $request = Request::create('/?sort=-priority;deadline;name');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $sorting = $dto->getSorting();

        $this->assertCount(3, $sorting);
        $this->assertEquals('priority', $sorting[0]->name);
        $this->assertEquals(SortDirection::DESC, $sorting[0]->direction);
        $this->assertEquals('deadline', $sorting[1]->name);
        $this->assertEquals(SortDirection::ASC, $sorting[1]->direction);
        $this->assertEquals('name', $sorting[2]->name);
        $this->assertEquals(SortDirection::ASC, $sorting[2]->direction);
    }

    public function testResolveWithPagination(): void
    {
        $request = Request::create('/?limit=50&offset=100');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $pagination = $dto->getPagination();

        $this->assertEquals(50, $pagination->limit);
        $this->assertEquals(100, $pagination->offset);
    }

    public function testResolveWithDefaultPagination(): void
    {
        $request = Request::create('/');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $pagination = $dto->getPagination();

        $this->assertEquals(20, $pagination->limit);
        $this->assertEquals(0, $pagination->offset);
    }

    public function testResolveIgnoresNonSearchDtoTypes(): void
    {
        $metadata = new ArgumentMetadata('other', 'string', false, false, null);
        $request = Request::create('/?filter[status]=todo');

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $this->assertEmpty($result);
    }

    public function testResolveWithMultipleConditionsSameField(): void
    {
        $request = Request::create('/?filter[status]=in:backlog,todo;neq:done');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $filters = $dto->getFilters();

        $this->assertCount(2, $filters);
        $this->assertEquals('status', $filters[0]->name);
        $this->assertEquals('status', $filters[1]->name);
    }

    public function testResolveWithValidProjectIdFilter(): void
    {
        $projectId = '550e8400-e29b-41d4-a716-446655440000';
        $request = Request::create('/?filter[projectId]='.$projectId);
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $filters = $dto->getFilters();

        $this->assertCount(1, $filters);
        $this->assertEquals('projectId', $filters[0]->name);
        $this->assertEquals($projectId, $filters[0]->value);
    }

    public function testResolveWithCommasSeparatingValues(): void
    {
        $request = Request::create('/?filter[status]=in:backlog,in_progress,done');
        $metadata = new ArgumentMetadata('criteria', SearchTaskDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $metadata));

        $dto = $result[0];
        $filters = $dto->getFilters();

        $this->assertCount(1, $filters);
        $this->assertEquals(['backlog', 'in_progress', 'done'], $filters[0]->value);
    }
}
