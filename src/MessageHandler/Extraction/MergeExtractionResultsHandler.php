<?php

namespace App\MessageHandler\Extraction;

use App\Component\Ai\Extractor\Content\Text;
use App\Component\Ai\Extractor\Exception\ExtractionException;
use App\Component\Ai\Extractor\ExtractionRequest;
use App\Component\Ai\Extractor\Extractor;
use App\Component\Ai\Prompt\PromptProvider;
use App\Message\Extraction\MergeExtractionResults;
use App\Service\Extraction\ExtractionManager;
use App\Service\Extraction\Fragment\FragmentContentAssembler;
use App\Service\Extraction\Target\TargetHandlerRegistry;
use App\Service\Extraction\UserPromptBuilder;
use OpenAI\Exceptions\ErrorException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class MergeExtractionResultsHandler
{
    public function __construct(
        private ExtractionManager $extractionManager,
        private Extractor $extractor,
        private PromptProvider $promptProvider,
        private UserPromptBuilder $userPromptBuilder,
        private TargetHandlerRegistry $targetHandlerRegistry,
        private FragmentContentAssembler $contentAssembler,
        #[Autowire(service: 'monolog.logger.extraction')]
        private LoggerInterface $logger,
    ) {
    }

    /** @throws \Throwable */
    public function __invoke(MergeExtractionResults $message): void
    {
        $extraction = $this->extractionManager->get($message->extractionId);

        $content = $this->contentAssembler->assemble($extraction);

        if (!$content) {
            $this->extractionManager->markFailed($extraction, 'No processable content in attachments.');

            return;
        }

        try {
            $content[] = new Text($this->userPromptBuilder->build($extraction));

            $request = new ExtractionRequest(
                $this->promptProvider->getSystemPrompt($extraction->targetType->value),
                $content,
                $this->targetHandlerRegistry->getSchemaClass($extraction->targetType),
            );

            $dto = $this->extractor->extract($request);

            $handler = $this->targetHandlerRegistry->get($extraction->targetType);
            $target = $handler->create($extraction, $dto);

            $this->extractionManager->markDone($extraction, $target->getRef());

            $this->logger->info('Extraction completed successfully.', [
                'id' => $message->extractionId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Extraction merge failed.', [
                'id' => $message->extractionId,
                'error' => $e->getMessage(),
            ]);

            if ($this->isTransient($e)) {
                throw $e;
            }

            $this->extractionManager->markFailed($extraction, $e->getMessage());
        }
    }

    private function isTransient(\Throwable $e): bool
    {
        if ($e instanceof \InvalidArgumentException || $e instanceof ExtractionException) {
            return false;
        }

        if ($e instanceof ErrorException) {
            return $e->getStatusCode() >= 500;
        }

        return true;
    }
}
