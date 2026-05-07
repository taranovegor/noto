<?php

namespace App\MessageHandler\Attachment;

use App\Component\Storage\ObjectStorage;
use App\Message\Attachment\DeleteFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteFileHandler
{
    public function __construct(
        private ObjectStorage $storage,
        #[Autowire('@monolog.logger.attachment')]
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteFile $message): void
    {
        try {
            $this->storage->delete($message->path);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to delete attachment file from storage: {message}', [
                'message' => $e->getMessage(),
                'attachmentId' => $message->id->toString(),
                'path' => $message->path,
            ]);
        }
    }
}
