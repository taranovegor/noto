<?php

namespace App\Tests\Unit\Component\Ai\Store\MessageHandler;

use App\Component\Ai\Store\Document\IndexableReference;
use App\Component\Ai\Store\Message\IndexObject;
use App\Component\Ai\Store\MessageHandler\IndexObjectHandler;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\AI\Store\IndexerInterface;

class IndexObjectHandlerTest extends TestCase
{
    private function makeMessage(): IndexObject
    {
        $task = new Task('Test');
        $taskId = (string) $task->getRef()->id;

        return new IndexObject(new IndexableReference(Task::class, $taskId));
    }

    /**
     * @throws \Throwable
     */
    public function testInvokeIndexesObjectAndLogsSuccess(): void
    {
        $message = $this->makeMessage();
        $indexer = $this->createMock(IndexerInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $indexer->expects($this->once())->method('index')->with((string) $message->reference);

        $handler = new IndexObjectHandler($indexer, $logger);
        $handler($message);
    }

    /**
     * @throws \Throwable
     */
    public function testInvokeLogsErrorAndRethrowsOnException(): void
    {
        $indexer = $this->createStub(IndexerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $exception = new \RuntimeException('indexing failed');
        $indexer->method('index')->willThrowException($exception);

        $logger->expects($this->once())->method('error');

        $handler = new IndexObjectHandler($indexer, $logger);

        $this->expectExceptionObject($exception);

        $handler($this->makeMessage());
    }
}
