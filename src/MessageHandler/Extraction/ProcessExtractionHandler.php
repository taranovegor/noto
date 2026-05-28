<?php

namespace App\MessageHandler\Extraction;

use App\Dto\Extraction\Fragment;
use App\Entity\Attachment;
use App\Enum\ExtractionStatus;
use App\Enum\LinkKind;
use App\Message\Extraction\MergeExtractionResults;
use App\Message\Extraction\ProcessAudioChunk;
use App\Message\Extraction\ProcessExtraction;
use App\Repository\ExtractionRepository;
use App\Service\Extraction\ExtractionManager;
use App\Service\Extraction\Processor\AttachmentProcessor;
use App\Service\Link\LinkResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ProcessExtractionHandler
{
    /**
     * @param iterable<AttachmentProcessor> $processors
     */
    public function __construct(
        private ExtractionRepository $extractionRepository,
        private ExtractionManager $extractionManager,
        private LinkResolver $linkResolver,
        #[AutowireIterator('app.extraction.attachment_processor')]
        private iterable $processors,
        private MessageBusInterface $messageBus,
        #[Autowire(service: 'monolog.logger.extraction')]
        private LoggerInterface $logger,
        private int $maxFileBytes = 50 * 1024 * 1024,
    ) {
    }

    /** @throws \Throwable */
    public function __invoke(ProcessExtraction $message): void
    {
        $extraction = $this->extractionRepository->find($message->extractionId);

        if (null === $extraction) {
            $this->logger->error('Extraction not found.', ['id' => $message->extractionId]);

            return;
        }

        if (ExtractionStatus::Done === $extraction->status) {
            $this->logger->info('Extraction already completed, skipping.', ['id' => $message->extractionId]);

            return;
        }

        if (null !== $extraction->fragments) {
            $this->logger->info('Extraction already prepared, sub-messages dispatched.', ['id' => $message->extractionId]);

            return;
        }

        $this->extractionManager->markProcessing($extraction);

        /** @var Attachment[] $attachments */
        $attachments = $this->linkResolver->resolve($extraction->getRef(), LinkKind::Reference, Attachment::class);

        if (!$attachments) {
            $this->logger->error('No attachments linked, marking as failed.', ['id' => $message->extractionId]);
            $this->extractionManager->markFailed($extraction, 'No attachments linked to extraction.');

            return;
        }

        foreach ($attachments as $attachment) {
            if (str_starts_with($attachment->mimeType, 'audio/')) {
                continue;
            }

            if ($attachment->size >= $this->maxFileBytes) {
                $this->extractionManager->markFailed($extraction, sprintf('Attachment "%s" exceeds the %d MB limit for model input.', $attachment->originFilename, intdiv($this->maxFileBytes, 1024 * 1024)));

                return;
            }
        }

        /** @var Fragment[] $fragments */
        $fragments = [];
        /** @var object[] $messages */
        $messages = [];

        foreach ($attachments as $attachment) {
            $processor = $this->resolveProcessor($attachment);

            if (null === $processor) {
                $this->logger->warning('No processor found for attachment.', [
                    'id' => $extraction->id,
                    'mimeType' => $attachment->mimeType,
                ]);
                continue;
            }

            foreach ($processor->plan($attachment) as $planned) {
                $id = Uuid::v7()->toRfc4122();

                if ($planned->isFailed()) {
                    $fragments[] = Fragment::failed($planned->type, $id, $planned->error ?? '');

                    continue;
                }

                \assert(null !== $planned->source);
                $source = $planned->source;

                if ($planned->type->isDeferred()) {
                    $fragments[] = Fragment::pending($planned->type, $id, $source->storageKey, $source->mimeType, $source->filename);
                    $messages[] = new ProcessAudioChunk($extraction->id, $id, $source->storageKey, $source->filename);
                } else {
                    $fragments[] = Fragment::reference($planned->type, $id, $source->storageKey, $source->mimeType, $source->filename);
                }
            }
        }

        if (empty($fragments)) {
            $this->extractionManager->markFailed($extraction, 'No processable content in attachments.');

            return;
        }

        $this->extractionManager->setFragments($extraction, $fragments);

        if (empty($messages)) {
            $this->messageBus->dispatch(new MergeExtractionResults($extraction->id));

            return;
        }

        foreach ($messages as $message) {
            $this->messageBus->dispatch($message);
        }
    }

    private function resolveProcessor(Attachment $attachment): ?AttachmentProcessor
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($attachment)) {
                return $processor;
            }
        }

        return null;
    }
}
