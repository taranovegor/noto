<?php

namespace App\Tests\Unit\Serializer;

use App\Entity\Task;
use App\Serializer\EntityNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Uid\Uuid;

class EntityNormalizerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->normalizer = new EntityNormalizer($this->entityManager);
    }

    public function testSupportsNormalization(): void
    {
        $task = new Task('Test');
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with(Task::class)
            ->willReturn(false);

        $supports = $this->normalizer->supportsNormalization($task);

        $this->assertTrue($supports);
    }

    public function testSupportsNormalizationReturnsFalseForNonObject(): void
    {
        $this->entityManager->expects($this->never())
            ->method('getMetadataFactory');

        $supports = $this->normalizer->supportsNormalization('not an object');

        $this->assertFalse($supports);
    }

    public function testNormalizeReturnsEntityId(): void
    {
        $task = new Task('Test');
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Task::class)
            ->willReturn($classMetadata);

        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->callback(function (Task $passedTask) use ($task) {
                return $passedTask === $task;
            }))
            ->willReturn([$task->id]);

        $result = $this->normalizer->normalize($task);

        $this->assertEquals($task->id, $result);
    }

    public function testSupportsDenormalization(): void
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with(Task::class)
            ->willReturn(false);

        $supports = $this->normalizer->supportsDenormalization(
            '019d5300-78a1-72a4-bc32-efc7212caba2',
            Task::class,
        );

        $this->assertTrue($supports);
    }

    public function testDenormalizeReturnsEntity(): void
    {
        $id = Uuid::v7();
        $task = new Task('Test');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->willReturn($task);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Task::class)
            ->willReturn($repository);

        $result = $this->normalizer->denormalize($id, Task::class);

        $this->assertSame($task, $result);
    }

    public function testDenormalizeThrowsNotNormalizableValueExceptionWhenEntityNotFound(): void
    {
        $id = Uuid::v7();

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($id))
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Task::class)
            ->willReturn($repository);

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessageMatches('/Entity.*Task.*not found/i');

        $this->normalizer->denormalize($id, Task::class);
    }

    public function testGetSupportedTypes(): void
    {
        $this->entityManager->expects($this->never())
            ->method('getMetadataFactory');

        $types = $this->normalizer->getSupportedTypes(null);

        $this->assertArrayHasKey('object', $types);
        $this->assertTrue($types['object']);
    }
}
