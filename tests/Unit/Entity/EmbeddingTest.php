<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Embedding;
use App\Entity\Ref;
use App\Enum\RefType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class EmbeddingTest extends TestCase
{
    public function testConstructorInitializesEmbedding(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Task);
        $vector = [0.1, 0.2, 0.3, 0.4, 0.5];
        $metadata = ['key' => 'value', 'count' => 42];

        $embedding = new Embedding($id, $parent, $vector, $metadata);

        $this->assertEquals($id, $embedding->id);
        $this->assertEquals($parent, $embedding->parent);
        $this->assertEquals($vector, $embedding->vector);
        $this->assertEquals($metadata, $embedding->metadata);
    }

    public function testConstructorInitializesTimestamps(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Task);
        $vector = [0.1, 0.2];
        $metadata = [];

        $before = new \DateTimeImmutable();
        $embedding = new Embedding($id, $parent, $vector, $metadata);
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $embedding->createdAt);
        $this->assertGreaterThanOrEqual($before, $embedding->createdAt);
        $this->assertLessThanOrEqual($after, $embedding->createdAt);

        $this->assertInstanceOf(\DateTimeImmutable::class, $embedding->updatedAt);
        $this->assertGreaterThanOrEqual($before, $embedding->updatedAt);
        $this->assertLessThanOrEqual($after, $embedding->updatedAt);
    }

    public function testPropertiesAreSetOnConstruction(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Task);
        $vector = array_fill(0, 10, 0.5);
        $metadata = ['source' => 'test', 'version' => 1];

        $embedding = new Embedding($id, $parent, $vector, $metadata);

        $this->assertSame($id, $embedding->id);
        $this->assertSame($parent, $embedding->parent);
        $this->assertCount(10, $embedding->vector);
        $this->assertCount(2, $embedding->metadata);
    }

    public function testEmbeddingWithEmptyVector(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Task);
        $vector = [];
        $metadata = [];

        $embedding = new Embedding($id, $parent, $vector, $metadata);

        $this->assertEmpty($embedding->vector);
        $this->assertEmpty($embedding->metadata);
    }

    public function testEmbeddingWithLargeVector(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Task);
        $vector = array_fill(0, 1024, 0.1);
        $metadata = [];

        $embedding = new Embedding($id, $parent, $vector, $metadata);

        $this->assertCount(1024, $embedding->vector);
    }

    public function testParentRefTypeIsPreserved(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Project);
        $vector = [0.1];
        $metadata = [];

        $embedding = new Embedding($id, $parent, $vector, $metadata);

        $this->assertEquals(RefType::Project, $embedding->parent->type);
    }

    public function testMetadataPreserveTypes(): void
    {
        $id = Uuid::v7();
        $parent = new Ref(RefType::Task);
        $vector = [0.1];
        $metadata = [
            'string' => 'test',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => ['nested' => 'value'],
        ];

        $embedding = new Embedding($id, $parent, $vector, $metadata);

        $this->assertEquals('test', $embedding->metadata['string']);
        $this->assertEquals(42, $embedding->metadata['int']);
        $this->assertEquals(3.14, $embedding->metadata['float']);
        $this->assertTrue($embedding->metadata['bool']);
        $this->assertNull($embedding->metadata['null']);
        $this->assertEquals(['nested' => 'value'], $embedding->metadata['array']);
    }
}
