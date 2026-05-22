<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Factory\Memo\MemoResponseDtoFactory;
use App\Mcp\Resource\MemoResource;
use App\Repository\MemoRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Memo\MemoManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MemoResourceTest extends TestCase
{
    private MemoResource $resource;
    private MemoManager $memoManager;
    private MemoResponseDtoFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $memoRepository = $this->createStub(MemoRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $this->memoManager = new MemoManager($memoRepository, $this->createStub(LinkerInterface::class), $flusher);
        $this->factory = $this->createStub(MemoResponseDtoFactory::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->resource = new MemoResource($this->memoManager, $this->factory);
    }

    public function testResourceHasNoteManager(): void
    {
        $this->assertInstanceOf(MemoManager::class, $this->memoManager);
    }

    public function testResourceHasFactory(): void
    {
        $this->assertInstanceOf(MemoResponseDtoFactory::class, $this->factory);
    }

    public function testResourceHasGetMethod(): void
    {
        $this->assertTrue(method_exists($this->resource, 'get'));
    }
}
