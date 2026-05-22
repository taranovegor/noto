<?php

namespace App\Tests\Unit\Factory\Memo;

use App\Entity\Memo;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Memo\MemoResponseDtoFactory;
use App\Service\Link\LinkResolver;
use PHPUnit\Framework\TestCase;

class MemoResponseDtoFactoryTest extends TestCase
{
    private MemoResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MemoResponseDtoFactory(
            $this->createStub(LinkResolver::class),
            new AttachmentResponseDtoFactory(),
        );
    }

    public function testCreateConvertsNoteToDto(): void
    {
        $content = '# Test Memo'."\n".'Test Content';
        $memo = new Memo($content);

        $dto = $this->factory->create($memo);

        $this->assertEquals($memo->id, $dto->id);
        $this->assertEquals($content, $dto->content);
    }

    public function testCreateWithAttachmentsReturnsAttachmentDtos(): void
    {
        $memo = new Memo('# Test Memo'."\n".'Content');

        $dto = $this->factory->create($memo);

        $this->assertNull($dto->attachments);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $memo = new Memo('# Timestamps Test'."\n".'Content');

        $dto = $this->factory->create($memo);

        $this->assertEquals($memo->createdAt, $dto->createdAt);
        $this->assertEquals($memo->updatedAt, $dto->updatedAt);
    }
}
