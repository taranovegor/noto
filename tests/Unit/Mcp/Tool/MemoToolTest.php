<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Component\Searcher\SearcherInterface;
use App\Factory\Memo\MemoResponseDtoFactory;
use App\Mcp\Tool\MemoTool;
use App\Repository\MemoRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Memo\MemoManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MemoToolTest extends TestCase
{
    private MemoTool $tool;
    private MemoManager $memoManager;
    private MemoResponseDtoFactory $factory;
    private SearcherInterface $searcher;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $memoRepository = $this->createStub(MemoRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->memoManager = new MemoManager($memoRepository, $this->createStub(LinkerInterface::class), $flusher);
        $this->factory = $this->createStub(MemoResponseDtoFactory::class);
        $this->searcher = $this->createStub(SearcherInterface::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->tool = new MemoTool($this->memoManager, $this->factory, $this->searcher);
    }

    public function testToolHasNoteManager(): void
    {
        $this->assertInstanceOf(MemoManager::class, $this->memoManager);
    }

    public function testToolHasFactory(): void
    {
        $this->assertInstanceOf(MemoResponseDtoFactory::class, $this->factory);
    }

    public function testToolHasCreateMethod(): void
    {
        $this->assertTrue(method_exists($this->tool, 'create'));
    }

    public function testToolHasUpdateMethod(): void
    {
        $this->assertTrue(method_exists($this->tool, 'update'));
    }

    public function testToolHasSearchMethod(): void
    {
        $this->assertTrue(method_exists($this->tool, 'search'));
    }

    public function testToolHasSearcher(): void
    {
        $this->assertInstanceOf(SearcherInterface::class, $this->searcher);
    }
}
