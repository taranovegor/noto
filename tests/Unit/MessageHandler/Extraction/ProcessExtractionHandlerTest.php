<?php

namespace App\Tests\Unit\MessageHandler\Extraction;

use App\Dto\Extraction\Fragment;
use App\Entity\Extraction;
use App\Enum\Extraction\FragmentType;
use App\Enum\ExtractionStatus;
use App\Enum\RefType;
use App\Message\Extraction\ProcessExtraction;
use App\MessageHandler\Extraction\ProcessExtractionHandler;
use App\Repository\ExtractionRepository;
use App\Service\Extraction\ExtractionManager;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Link\LinkResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ProcessExtractionHandlerTest extends TestCase
{
    public function testMarksFailedWhenExtractionNotFound(): void
    {
        $repository = $this->createStub(ExtractionRepository::class);
        $repository->method('find')->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            $this->stringContains('not found'),
        );

        $handler = new ProcessExtractionHandler(
            $repository,
            $this->makeExtractionManager($repository),
            $this->createStub(LinkResolver::class),
            [],
            $this->createStub(MessageBusInterface::class),
            $logger,
        );

        $handler(new ProcessExtraction(Uuid::v7()));
    }

    public function testSkipsAlreadyDone(): void
    {
        $extraction = new Extraction(RefType::Note);
        $extraction->status = ExtractionStatus::Done;

        $repository = $this->createStub(ExtractionRepository::class);
        $repository->method('find')->willReturn($extraction);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            $this->stringContains('already completed'),
        );

        $handler = new ProcessExtractionHandler(
            $repository,
            $this->makeExtractionManager($repository),
            $this->createStub(LinkResolver::class),
            [],
            $this->createStub(MessageBusInterface::class),
            $logger,
        );

        $handler(new ProcessExtraction(Uuid::v7()));

        $this->assertSame(ExtractionStatus::Done, $extraction->status);
    }

    public function testSkipsAlreadyPrepared(): void
    {
        $extraction = new Extraction(RefType::Note);
        $extraction->setFragments([Fragment::reference(FragmentType::Document, 'idx', 'key', 'text/plain', 'note.txt')]);

        $repository = $this->createStub(ExtractionRepository::class);
        $repository->method('find')->willReturn($extraction);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            $this->stringContains('already prepared'),
        );

        $handler = new ProcessExtractionHandler(
            $repository,
            $this->makeExtractionManager($repository),
            $this->createStub(LinkResolver::class),
            [],
            $this->createStub(MessageBusInterface::class),
            $logger,
        );

        $handler(new ProcessExtraction(Uuid::v7()));

        $this->assertSame(ExtractionStatus::Pending, $extraction->status);
    }

    public function testMarksFailedWhenNoAttachments(): void
    {
        $extraction = new Extraction(RefType::Note);

        $repository = $this->createStub(ExtractionRepository::class);
        $repository->method('find')->willReturn($extraction);

        $linkResolver = $this->createStub(LinkResolver::class);
        $linkResolver->method('resolve')->willReturn([]);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $handler = new ProcessExtractionHandler(
            $repository,
            $this->makeExtractionManager($repository),
            $linkResolver,
            [],
            $messageBus,
            $this->createStub(LoggerInterface::class),
        );

        $handler(new ProcessExtraction($extraction->id));

        $this->assertSame(ExtractionStatus::Failed, $extraction->status);
        $this->assertSame('No attachments linked to extraction.', $extraction->errorMessage);
    }

    private function makeExtractionManager(ExtractionRepository $repository): ExtractionManager
    {
        return new ExtractionManager(
            $repository,
            $this->createStub(LinkerInterface::class),
            $this->createStub(Flusher::class),
            $this->createStub(EntityManagerInterface::class),
        );
    }
}
