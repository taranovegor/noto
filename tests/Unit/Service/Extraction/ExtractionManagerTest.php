<?php

namespace App\Tests\Unit\Service\Extraction;

use App\Dto\Extraction\CreateExtractionDto;
use App\Entity\Attachment;
use App\Entity\Extraction;
use App\Entity\Ref;
use App\Enum\ExtractionStatus;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Exception\EntityNotFoundException;
use App\Repository\ExtractionRepository;
use App\Service\Extraction\ExtractionManager;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExtractionManagerTest extends TestCase
{
    public function testStartCreatesExtraction(): void
    {
        $repository = $this->createMock(ExtractionRepository::class);
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $repository->expects($this->once())->method('add');
        $linker->expects($this->once())->method('link')->with(
            $this->isInstanceOf(Ref::class),
            $this->isInstanceOf(Ref::class),
            LinkKind::Reference,
        );
        $flusher->expects($this->once())->method('flush');

        $manager = new ExtractionManager($repository, $linker, $flusher, $this->createStub(\Doctrine\ORM\EntityManagerInterface::class));

        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getRef')->willReturn(new Ref(RefType::Attachment));

        $dto = new CreateExtractionDto(
            [$attachment],
            RefType::Note,
        );

        $result = $manager->start($dto);

        $this->assertInstanceOf(Extraction::class, $result);
        $this->assertSame(ExtractionStatus::Pending, $result->status);
        $this->assertSame(RefType::Note, $result->targetType);
    }

    public function testStartWithMultipleAttachmentsLinksAll(): void
    {
        $repository = $this->createStub(ExtractionRepository::class);
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createStub(Flusher::class);

        $linker->expects($this->exactly(2))->method('link');

        $manager = new ExtractionManager($repository, $linker, $flusher, $this->createStub(\Doctrine\ORM\EntityManagerInterface::class));

        $a1 = $this->createStub(Attachment::class);
        $a1->method('getRef')->willReturn(new Ref(RefType::Attachment));
        $a2 = $this->createStub(Attachment::class);
        $a2->method('getRef')->willReturn(new Ref(RefType::Attachment));

        $dto = new CreateExtractionDto([$a1, $a2], RefType::Note);

        $manager->start($dto);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $id = Uuid::v7();
        $repository = $this->createStub(ExtractionRepository::class);
        $repository->method('find')->willReturn(null);

        $manager = new ExtractionManager(
            $repository,
            $this->createStub(LinkerInterface::class),
            $this->createStub(Flusher::class),
            $this->createStub(\Doctrine\ORM\EntityManagerInterface::class),
        );

        $this->expectException(EntityNotFoundException::class);
        $manager->get($id);
    }

    public function testMarkProcessing(): void
    {
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $manager = new ExtractionManager(
            $this->createStub(ExtractionRepository::class),
            $this->createStub(LinkerInterface::class),
            $flusher,
            $this->createStub(\Doctrine\ORM\EntityManagerInterface::class),
        );

        $extraction = new Extraction(RefType::Note);
        $manager->markProcessing($extraction);

        $this->assertSame(ExtractionStatus::Processing, $extraction->status);
        $this->assertNotNull($extraction->startedAt);
    }

    public function testMarkDone(): void
    {
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $linker->expects($this->once())->method('link')->with(
            $this->isInstanceOf(Ref::class),
            $this->isInstanceOf(Ref::class),
            LinkKind::Derivation,
        );
        $flusher->expects($this->once())->method('flush');

        $manager = new ExtractionManager(
            $this->createStub(ExtractionRepository::class),
            $linker,
            $flusher,
            $this->createStub(\Doctrine\ORM\EntityManagerInterface::class),
        );

        $extraction = new Extraction(RefType::Note);
        $targetRef = new Ref(RefType::Note);

        $manager->markDone($extraction, $targetRef);

        $this->assertSame(ExtractionStatus::Done, $extraction->status);
    }

    public function testMarkFailed(): void
    {
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $manager = new ExtractionManager(
            $this->createStub(ExtractionRepository::class),
            $this->createStub(LinkerInterface::class),
            $flusher,
            $this->createStub(\Doctrine\ORM\EntityManagerInterface::class),
        );

        $extraction = new Extraction(RefType::Note);
        $manager->markFailed($extraction, 'API error');

        $this->assertSame(ExtractionStatus::Failed, $extraction->status);
        $this->assertSame('API error', $extraction->errorMessage);
    }
}
