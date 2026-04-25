<?php

namespace App\Tests\Unit\Component\Ai\Store\Document;

use App\Component\Ai\Store\Config\IndexableConfig;
use App\Component\Ai\Store\Document\IndexableEntityLoader;
use App\Component\Ai\Store\Document\IndexableReference;
use App\Component\Ai\Store\Document\TextDocumentFactory;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;

class EntityLoaderTest extends TestCase
{
    private EntityManagerInterface $em;
    private TextDocumentFactory $factory;
    private IndexableEntityLoader $loader;

    private static IndexableConfig $config;

    protected function setUp(): void
    {
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->factory = $this->createStub(TextDocumentFactory::class);
        self::$config = new IndexableConfig([Task::class => ['fields' => ['name'], 'identifierField' => 'id']]);

        $this->loader = new IndexableEntityLoader($this->em, $this->factory, self::$config);
    }

    private function mockRepository(array $entities): EntityRepository
    {
        $query = $this->createStub(Query::class);
        $query->method('getResult')->willReturn($entities);

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);

        return $repo;
    }

    public function testLoadYieldsDocumentsForAllConfiguredEntities(): void
    {
        $task = new Task('Test');
        $doc = new TextDocument('id', 'Test', new Metadata([]));

        $this->em->method('getRepository')->willReturn($this->mockRepository([$task]));
        $this->factory->method('create')->willReturn($doc);

        $result = iterator_to_array($this->loader->load());

        $this->assertCount(1, $result);
        $this->assertSame($doc, $result[0]);
    }

    public function testLoadWithSourceQueriesOnlyThatClass(): void
    {
        $task = new Task('Test');
        $doc = new TextDocument('id', 'Test', new Metadata([]));
        $taskId = (string) $task->getRef()->id;
        $source = (string) new IndexableReference(Task::class, $taskId);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Task::class)
            ->willReturn($this->mockRepository([$task]));

        $factory = $this->createStub(TextDocumentFactory::class);
        $factory->method('create')->willReturn($doc);

        $loader = new IndexableEntityLoader($em, $factory, self::$config);
        $result = iterator_to_array($loader->load($source));

        $this->assertCount(1, $result);
    }

    public function testLoadSkipsEntityWhenFactoryThrowsInvalidArgument(): void
    {
        $this->em->method('getRepository')->willReturn($this->mockRepository([new Task('Test')]));
        $this->factory->method('create')->willThrowException(new \InvalidArgumentException());

        $result = iterator_to_array($this->loader->load());

        $this->assertCount(0, $result);
    }

    public function testLoadSkipsEntityWhenFactoryThrowsRuntimeException(): void
    {
        $this->em->method('getRepository')->willReturn($this->mockRepository([new Task('Test')]));
        $this->factory->method('create')->willThrowException(new \RuntimeException());

        $result = iterator_to_array($this->loader->load());

        $this->assertCount(0, $result);
    }
}
