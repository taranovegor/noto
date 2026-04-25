<?php

namespace App\Component\Ai\Store\MessageHandler;

use App\Component\Ai\Store\Message\IndexObject;
use Psr\Log\LoggerInterface;
use Symfony\AI\Store\IndexerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IndexObjectHandler
{
    public function __construct(
        private IndexerInterface $indexer,
        #[Autowire('@monolog.logger.ai_index')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(IndexObject $index): void
    {
        $reference = (string) $index->reference;

        $this->logger->info('Indexing object.', ['reference' => $reference]);

        try {
            $this->indexer->index($reference);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to index object.', ['reference' => $reference, 'error' => $e->getMessage()]);

            throw $e;
        }

        $this->logger->info('Object indexed successfully.', ['reference' => $reference]);
    }
}
