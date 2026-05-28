<?php

namespace App\MessageHandler\Extraction;

use App\Component\Ai\Transcriber\Transcriber;
use App\Component\Storage\ObjectStorage;
use App\Dto\Extraction\FragmentResult;
use App\Enum\Extraction\FragmentType;
use App\Message\Extraction\ProcessAudioChunk;
use App\Service\Extraction\Fragment\FragmentCompletion;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessAudioChunkHandler
{
    public function __construct(
        private Transcriber $transcriber,
        private ObjectStorage $tempStorage,
        private FragmentCompletion $completion,
        #[Autowire(service: 'monolog.logger.extraction')]
        private LoggerInterface $logger,
    ) {
    }

    /** @throws \Throwable */
    public function __invoke(ProcessAudioChunk $message): void
    {
        try {
            $url = $this->tempStorage->downloadUrl($message->storageKey, $message->filename);
            $transcript = $this->transcriber->transcribe($url);
            $result = FragmentResult::success($transcript);
        } catch (\Throwable $e) {
            $this->logger->error('Audio transcription failed.', [
                'extractionId' => $message->extractionId,
                'fragmentId' => $message->fragmentId,
                'error' => $e->getMessage(),
            ]);

            $result = FragmentResult::failure($e->getMessage());
        } finally {
            try {
                $this->tempStorage->delete($message->storageKey);
            } catch (\Throwable) {
            }
        }

        $this->completion->complete(
            $message->extractionId,
            FragmentType::AudioTranscript,
            $message->fragmentId,
            $result,
        );
    }
}
