<?php

namespace App\Service\Extraction\Target;

use App\Dto\Note\CreateNoteDto;
use App\Dto\Note\NoteExtractedContent;
use App\Entity\Attachment;
use App\Entity\Extraction;
use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Service\Link\LinkResolver;
use App\Service\Note\NoteManager;
use App\Service\Notebook\NotebookManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.extraction.target_handler', ['priority' => 10])]
final readonly class NoteExtractionTargetHandler implements ExtractionTargetHandler
{
    public function __construct(
        private NoteManager $noteManager,
        private NotebookManager $notebookManager,
        private LinkResolver $linkResolver,
    ) {
    }

    public function supports(RefType $type): bool
    {
        return RefType::Note === $type;
    }

    public function getOutputSchema(): string
    {
        return NoteExtractedContent::class;
    }

    public function create(Extraction $extraction, object $dto): ReferenceableInterface
    {
        \assert($dto instanceof NoteExtractedContent);

        if (null === $extraction->targetParent) {
            throw new \RuntimeException('targetParent (notebook) is required for Note extraction.');
        }

        $notebook = $this->notebookManager->get($extraction->targetParent->id);

        /** @var Attachment[] $attachments */
        $attachments = $this->linkResolver->resolve($extraction->getRef(), LinkKind::Reference, Attachment::class);
        $dto = new CreateNoteDto($dto->title, $dto->content, $attachments);

        return $this->noteManager->create($notebook, $dto);
    }
}
