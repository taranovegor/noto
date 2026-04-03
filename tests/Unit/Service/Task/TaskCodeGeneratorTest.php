<?php

namespace App\Tests\Unit\Service\Task;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Service\Task\TaskCodeGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TaskCodeGeneratorTest extends TestCase
{
    private ProjectRepository&MockObject $repository;
    private TaskCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectRepository::class);
        $this->generator = new TaskCodeGenerator($this->repository);
    }

    public function testGenerateReturnsFormattedCode(): void
    {
        $projectId = Uuid::v7();
        $project = new Project('Test Project', 'TST');

        $statement = $this->createMock(Result::class);
        $statement->expects($this->once())->method('fetchOne')->willReturn(1);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('UPDATE projects SET task_counter = task_counter + 1'),
                $this->callback(function (array $params) use ($project) {
                    return isset($params['id']) && $params['id'] === $project->id;
                })
            )
            ->willReturn($statement);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())->method('getTableName')->willReturn('projects');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Project::class)
            ->willReturn($classMetadata);
        $entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $code = $this->generator->generate($project);

        $this->assertEquals('TST-1', $code);
    }

    public function testGenerateIncrementCounter(): void
    {
        $project = new Project('Another Project', 'ANO');

        $statement = $this->createMock(Result::class);
        $statement->expects($this->once())->method('fetchOne')->willReturn(42);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('UPDATE projects SET task_counter = task_counter + 1'),
                $this->callback(function (array $params) use ($project) {
                    return isset($params['id']) && $params['id'] === $project->id;
                })
            )
            ->willReturn($statement);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())->method('getTableName')->willReturn('projects');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Project::class)
            ->willReturn($classMetadata);
        $entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $code = $this->generator->generate($project);

        $this->assertEquals('ANO-42', $code);
    }
}
