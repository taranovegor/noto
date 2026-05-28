<?php

namespace App\Tests\Unit\Service\Extraction;

use App\Dto\Note\NoteExtractedContent;
use App\Entity\Extraction;
use App\Entity\Note;
use App\Entity\Notebook;
use App\Entity\Ref;
use App\Enum\RefType;
use App\Repository\NotebookRepository;
use App\Repository\NoteRepository;
use App\Service\Extraction\Target\NoteExtractionTargetHandler;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Link\LinkResolver;
use App\Service\Note\NoteManager;
use App\Service\Notebook\NotebookManager;
use PHPUnit\Framework\TestCase;

class NoteExtractionTargetHandlerTest extends TestCase
{
    public function testSupportsNoteType(): void
    {
        $handler = $this->makeHandler();

        $this->assertTrue($handler->supports(RefType::Note));
        $this->assertFalse($handler->supports(RefType::Task));
        $this->assertFalse($handler->supports(RefType::Notebook));
    }

    public function testGetOutputSchema(): void
    {
        $handler = $this->makeHandler();

        $this->assertSame(NoteExtractedContent::class, $handler->getOutputSchema());
    }

    public function testCreateNote(): void
    {
        $notebook = new Notebook('NB', 'Description');
        $targetRef = new Ref(RefType::Notebook);

        $notebookRepository = $this->createStub(NotebookRepository::class);
        $notebookRepository->method('find')->willReturn($notebook);

        $notebookManager = new NotebookManager($notebookRepository, $this->createStub(Flusher::class));

        $noteRepository = $this->createMock(NoteRepository::class);
        $noteRepository->expects($this->once())->method('add');

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $noteManager = new NoteManager($noteRepository, $this->createStub(LinkerInterface::class), $flusher);

        $linkResolver = $this->createStub(LinkResolver::class);
        $linkResolver->method('resolve')->willReturn([]);

        $handler = new NoteExtractionTargetHandler($noteManager, $notebookManager, $linkResolver);

        $extraction = new Extraction(RefType::Note, $targetRef);
        $dto = new NoteExtractedContent(title: 'Title', content: 'Content');

        $result = $handler->create($extraction, $dto);

        $this->assertInstanceOf(Note::class, $result);
        $this->assertSame('Title', $result->title);
        $this->assertSame('Content', $result->content);
    }

    public function testCreateThrowsWithoutTargetParent(): void
    {
        $handler = $this->makeHandler();

        $extraction = new Extraction(RefType::Note);
        $dto = new NoteExtractedContent(title: 'Title', content: 'Content');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('targetParent');

        $handler->create($extraction, $dto);
    }

    private function makeHandler(): NoteExtractionTargetHandler
    {
        return new NoteExtractionTargetHandler(
            $this->createRealNoteManager(),
            $this->createRealNotebookManager(),
            $this->createStub(LinkResolver::class),
        );
    }

    private function createRealNoteManager(): NoteManager
    {
        return new NoteManager(
            $this->createStub(NoteRepository::class),
            $this->createStub(LinkerInterface::class),
            $this->createStub(Flusher::class),
        );
    }

    private function createRealNotebookManager(): NotebookManager
    {
        return new NotebookManager(
            $this->createStub(NotebookRepository::class),
            $this->createStub(Flusher::class),
        );
    }
}
