<?php

namespace App\Tests\Integration\Service\Embedding;

use App\Entity\Embedding;
use App\Entity\Ref;
use App\Enum\RefType;
use App\Service\Embedding\EmbeddingStore;
use Doctrine\ORM\EntityManager;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\Query\VectorQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class EmbeddingStoreTest extends KernelTestCase
{
    private EmbeddingStore $embeddingStore;
    private EntityManager $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->embeddingStore = self::getContainer()->get(EmbeddingStore::class);

        // Clean up existing data
        $this->em->createQuery('DELETE FROM App\Entity\Embedding')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Ref')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }

    private function createVector(int $dimension = 1024, float $value = 0.1): Vector
    {
        return new Vector(array_fill(0, $dimension, $value));
    }

    public function testAddSingleDocument(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $document = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata([Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()])
        );

        $this->embeddingStore->add($document);

        $embeddings = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(1, $embeddings);
    }

    public function testAddMultipleDocuments(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $documents = [
            new VectorDocument(
                Uuid::v7()->toRfc4122(),
                $this->createVector(1024, 0.1),
                new Metadata([Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()])
            ),
            new VectorDocument(
                Uuid::v7()->toRfc4122(),
                $this->createVector(1024, 0.2),
                new Metadata([Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()])
            ),
        ];

        $this->embeddingStore->add($documents);

        $embeddings = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(2, $embeddings);
    }

    public function testAddDocumentsWithDifferentParents(): void
    {
        $parent1 = new Ref(RefType::Task);
        $parent2 = new Ref(RefType::Task);
        $this->em->persist($parent1);
        $this->em->persist($parent2);
        $this->em->flush();

        $documents = [
            new VectorDocument(
                Uuid::v7()->toRfc4122(),
                $this->createVector(),
                new Metadata([Metadata::KEY_PARENT_ID => $parent1->id->toRfc4122()])
            ),
            new VectorDocument(
                Uuid::v7()->toRfc4122(),
                $this->createVector(),
                new Metadata([Metadata::KEY_PARENT_ID => $parent2->id->toRfc4122()])
            ),
        ];

        $this->embeddingStore->add($documents);

        $embeddings = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(2, $embeddings);
    }

    public function testAddDocumentsReplacePreviousOnes(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $document1 = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata([Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()])
        );

        $this->embeddingStore->add($document1);
        $this->em->clear();

        $countBefore = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(1, $countBefore);

        $document2 = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata([Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()])
        );

        $this->embeddingStore->add($document2);

        $countAfter = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(1, $countAfter);
    }

    public function testAddDocumentWithoutParentThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to save Embedding for documents with Ref');

        $document = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata([Metadata::KEY_PARENT_ID => Uuid::v7()->toRfc4122()])
        );

        $this->embeddingStore->add($document);
    }

    public function testAddDocumentWithoutParentIdThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Parent id must be set for document');

        $document = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata([])
        );

        $this->embeddingStore->add($document);
    }

    public function testAddDocumentWithInvalidIdThrowsException(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid document id');

        $document = new VectorDocument(
            'not-a-uuid',
            $this->createVector(),
            new Metadata([Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()])
        );

        $this->embeddingStore->add($document);
    }

    public function testAddDocumentWithInvalidParentIdThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parent id');

        $document = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata([Metadata::KEY_PARENT_ID => 'not-a-uuid'])
        );

        $this->embeddingStore->add($document);
    }

    public function testRemoveByEmbeddingId(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $embedding = new Embedding(
            Uuid::v7(),
            $parent,
            array_fill(0, 1024, 0.1),
            ['key' => 'value']
        );
        $this->em->persist($embedding);
        $this->em->flush();

        $this->embeddingStore->remove($embedding->id->toRfc4122());

        $count = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(0, $count);
    }

    public function testRemoveMultipleEmbeddingIds(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $embedding1 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.1), []);
        $embedding2 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.2), []);
        $embedding3 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.3), []);

        $this->em->persist($embedding1);
        $this->em->persist($embedding2);
        $this->em->persist($embedding3);
        $this->em->flush();

        $this->embeddingStore->remove([
            $embedding1->id->toRfc4122(),
            $embedding2->id->toRfc4122(),
        ]);

        $count = $this->em->createQuery('SELECT COUNT(e) FROM App\Entity\Embedding e')->getSingleScalarResult();
        $this->assertEquals(1, $count);
    }

    public function testQuerySearchesByVector(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $embedding1 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.1), ['label' => 'first']);
        $embedding2 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.4), ['label' => 'second']);

        $this->em->persist($embedding1);
        $this->em->persist($embedding2);
        $this->em->flush();

        $query = new VectorQuery($this->createVector(1024, 0.1));
        $results = iterator_to_array($this->embeddingStore->query($query, ['limit' => 10]));

        $this->assertCount(2, $results);
    }

    public function testQueryReturnsResultsOrderedByDistance(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $v1 = array_fill(0, 1024, 0.1);
        $v2 = array_fill(0, 1024, 0.9);

        $embedding1 = new Embedding(Uuid::v7(), $parent, $v1, []);
        $embedding2 = new Embedding(Uuid::v7(), $parent, $v2, []);

        $this->em->persist($embedding1);
        $this->em->persist($embedding2);
        $this->em->flush();

        $query = new VectorQuery($this->createVector(1024, 0.1));
        $results = iterator_to_array($this->embeddingStore->query($query, ['limit' => 10]));

        $this->assertCount(2, $results);
        $this->assertLessThan($results[1]->getScore(), $results[0]->getScore());
    }

    public function testQueryRespectLimit(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $parent = new Ref(RefType::Task);
            $this->em->persist($parent);
            $embedding = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.1 * $i), []);
            $this->em->persist($embedding);
        }
        $this->em->flush();

        $query = new VectorQuery($this->createVector(1024, 0.5));
        $results = iterator_to_array($this->embeddingStore->query($query, ['limit' => 3]));

        $this->assertCount(3, $results);
    }

    public function testQueryWithMaxScore(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $embedding1 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.1), []);
        $embedding2 = new Embedding(Uuid::v7(), $parent, array_fill(0, 1024, 0.9), []);

        $this->em->persist($embedding1);
        $this->em->persist($embedding2);
        $this->em->flush();

        $query = new VectorQuery($this->createVector(1024, 0.1));
        $results = iterator_to_array($this->embeddingStore->query($query, ['limit' => 10, 'maxScore' => 0.5]));

        $this->assertCount(1, $results);
        $this->assertLessThanOrEqual(0.5, $results[0]->getScore());
    }

    public function testQueryWithWhereClause(): void
    {
        $parent1 = new Ref(RefType::Task);
        $parent2 = new Ref(RefType::Project);
        $this->em->persist($parent1);
        $this->em->persist($parent2);
        $this->em->flush();

        $embedding1 = new Embedding(Uuid::v7(), $parent1, array_fill(0, 1024, 0.1), []);
        $embedding2 = new Embedding(Uuid::v7(), $parent2, array_fill(0, 1024, 0.1), []);

        $this->em->persist($embedding1);
        $this->em->persist($embedding2);
        $this->em->flush();

        $query = new VectorQuery($this->createVector(1024, 0.1));
        $results = iterator_to_array($this->embeddingStore->query($query, [
            'limit' => 10,
            'where' => 'p.type = :type',
            'params' => ['type' => RefType::Task],
        ]));

        $this->assertCount(1, $results);
    }

    public function testSupportsVectorQuery(): void
    {
        $this->assertTrue($this->embeddingStore->supports(VectorQuery::class));
    }

    public function testDoesNotSupportOtherQueries(): void
    {
        $this->assertFalse($this->embeddingStore->supports('SomeOtherQuery'));
    }

    public function testQueryThrowsExceptionForInvalidQuery(): void
    {
        $this->expectException(\TypeError::class);

        $invalidQuery = new class {};
        $this->embeddingStore->query($invalidQuery);
    }

    public function testAddPreservesMetadata(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $metadata = ['source' => 'test', 'version' => 1, 'nested' => ['key' => 'value']];
        $document = new VectorDocument(
            Uuid::v7()->toRfc4122(),
            $this->createVector(),
            new Metadata(array_merge($metadata, [Metadata::KEY_PARENT_ID => $parent->id->toRfc4122()]))
        );

        $this->embeddingStore->add($document);

        $embedding = $this->em->createQuery(
            'SELECT e FROM App\Entity\Embedding e'
        )->getSingleResult();

        $this->assertEquals('test', $embedding->metadata['source']);
        $this->assertEquals(1, $embedding->metadata['version']);
        $this->assertEquals(['key' => 'value'], $embedding->metadata['nested']);
        $this->assertArrayNotHasKey(Metadata::KEY_PARENT_ID, $embedding->metadata);
    }

    public function testQueryPreservesParentIdInMetadata(): void
    {
        $parent = new Ref(RefType::Task);
        $this->em->persist($parent);
        $this->em->flush();

        $embedding = new Embedding(
            Uuid::v7(),
            $parent,
            array_fill(0, 1024, 0.1),
            ['source' => 'test']
        );
        $this->em->persist($embedding);
        $this->em->flush();

        $query = new VectorQuery($this->createVector());
        $results = iterator_to_array($this->embeddingStore->query($query));

        $this->assertCount(1, $results);
        $this->assertEquals($parent->id->toRfc4122(), $results[0]->getMetadata()->getParentId());

        $metadataArray = $results[0]->getMetadata()->getArrayCopy();
        $this->assertEquals('test', $metadataArray['source']);
    }
}
