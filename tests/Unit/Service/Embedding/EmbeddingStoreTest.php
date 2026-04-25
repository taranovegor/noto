<?php

namespace App\Tests\Unit\Service\Embedding;

use App\Repository\EmbeddingRepository;
use App\Repository\RefRepository;
use App\Service\Embedding\EmbeddingStore;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Query\VectorQuery;
use Symfony\Component\Uid\Uuid;

class EmbeddingStoreTest extends TestCase
{
    private EmbeddingRepository $embeddingRepository;
    private RefRepository $refRepository;
    private EntityManagerInterface $em;
    private EmbeddingStore $store;

    protected function setUp(): void
    {
        $this->embeddingRepository = $this->createStub(EmbeddingRepository::class);
        $this->refRepository = $this->createStub(RefRepository::class);
        $this->em = $this->createStub(EntityManagerInterface::class);

        $this->store = new EmbeddingStore($this->embeddingRepository, $this->refRepository, $this->em);
    }

    private function makeDocument(string $id, string $parentId): VectorDocument
    {
        return new VectorDocument(
            $id,
            new Vector([0.1, 0.2, 0.3]),
            new Metadata([Metadata::KEY_PARENT_ID => $parentId]),
        );
    }

    private function stubDeleteQb(EmbeddingRepository $repo): void
    {
        $query = $this->createStub(Query::class);

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repo->method('createQueryBuilder')->willReturn($qb);
    }

    // --- validation ---

    public function testAddThrowsOnInvalidDocumentId(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->store->add(new VectorDocument(
            'not-a-uuid',
            new Vector([0.1]),
            new Metadata([Metadata::KEY_PARENT_ID => Uuid::v7()->toString()]),
        ));
    }

    public function testAddThrowsOnMissingParentId(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->store->add(new VectorDocument(
            Uuid::v7()->toString(),
            new Vector([0.1]),
            new Metadata([]),
        ));
    }

    public function testAddThrowsOnInvalidParentId(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->store->add(new VectorDocument(
            Uuid::v7()->toString(),
            new Vector([0.1]),
            new Metadata([Metadata::KEY_PARENT_ID => 'not-a-uuid']),
        ));
    }

    // --- add() ---

    public function testAddPersistsEmbeddingsAndCommits(): void
    {
        $parentId = Uuid::v7()->toString();
        $doc = $this->makeDocument(Uuid::v7()->toString(), $parentId);

        $embeddingRepo = $this->createMock(EmbeddingRepository::class);
        $refRepo = $this->createStub(RefRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $refRepo->method('find')->willReturn($this->createStub(\App\Entity\Ref::class));
        $this->stubDeleteQb($embeddingRepo);

        $em->expects($this->once())->method('beginTransaction');
        $em->expects($this->once())->method('flush');
        $em->expects($this->once())->method('commit');
        $embeddingRepo->expects($this->once())->method('add');

        (new EmbeddingStore($embeddingRepo, $refRepo, $em))->add($doc);
    }

    public function testAddThrowsRuntimeExceptionWhenRefNotFound(): void
    {
        $parentId = Uuid::v7()->toString();
        $doc = $this->makeDocument(Uuid::v7()->toString(), $parentId);

        $this->refRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($parentId);

        $this->store->add($doc);
    }

    public function testAddRollsBackTransactionOnException(): void
    {
        $parentId = Uuid::v7()->toString();
        $doc = $this->makeDocument(Uuid::v7()->toString(), $parentId);

        $embeddingRepo = $this->createStub(EmbeddingRepository::class);
        $refRepo = $this->createStub(RefRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $refRepo->method('find')->willReturn($this->createStub(\App\Entity\Ref::class));

        $query = $this->createStub(Query::class);
        $query->method('execute')->willThrowException(new \RuntimeException('db error'));
        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $embeddingRepo->method('createQueryBuilder')->willReturn($qb);

        $em->expects($this->once())->method('rollback');
        $em->expects($this->never())->method('commit');

        $this->expectException(\RuntimeException::class);

        (new EmbeddingStore($embeddingRepo, $refRepo, $em))->add($doc);
    }

    // --- remove() ---

    public function testRemoveDeletesByIds(): void
    {
        $ids = [Uuid::v7()->toString(), Uuid::v7()->toString()];

        $query = $this->createStub(Query::class);
        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->embeddingRepository->method('createQueryBuilder')->willReturn($qb);

        $this->store->remove($ids);

        $this->addToAssertionCount(1);
    }

    // --- query() ---

    public function testQueryThrowsForNonVectorQuery(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        iterator_to_array($this->store->query($this->createStub(\Symfony\AI\Store\Query\QueryInterface::class)));
    }

    public function testQueryReturnsVectorDocuments(): void
    {
        $docId = Uuid::v7();
        $parentId = Uuid::v7();

        $rows = [[
            'id' => $docId,
            'vector' => [0.1, 0.2, 0.3],
            'metadata' => [],
            'parent_id' => $parentId,
            'score' => 0.42,
        ]];

        $query = $this->createStub(Query::class);
        $query->method('getResult')->willReturn($rows);

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->embeddingRepository->method('createQueryBuilder')->willReturn($qb);

        $vectorQuery = new VectorQuery(new Vector([0.1, 0.2, 0.3]));

        $results = iterator_to_array($this->store->query($vectorQuery));

        $this->assertCount(1, $results);
        $this->assertSame((string) $docId, $results[0]->getId());
        $this->assertSame((string) $parentId, $results[0]->getMetadata()[Metadata::KEY_PARENT_ID]);
        $this->assertSame(0.42, $results[0]->getScore());
    }

    // --- supports() ---

    public function testSupportsVectorQuery(): void
    {
        $this->assertTrue($this->store->supports(VectorQuery::class));
    }

    public function testDoesNotSupportArbitraryQueryClass(): void
    {
        $this->assertFalse($this->store->supports('App\Store\Query\SomeOtherQuery'));
    }
}
