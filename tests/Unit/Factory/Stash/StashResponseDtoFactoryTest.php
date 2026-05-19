<?php

namespace App\Tests\Unit\Factory\Stash;

use App\Dto\Stash\StashResponseDto;
use App\Entity\Attachment;
use App\Entity\Stash;
use App\Enum\AttachmentStatus;
use App\Enum\LinkKind;
use App\Enum\StashType;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Stash\StashResponseDtoFactory;
use App\Service\Link\LinkResolver;
use PHPUnit\Framework\TestCase;

class StashResponseDtoFactoryTest extends TestCase
{
    private function makeFactory(?LinkResolver $linkResolver = null): StashResponseDtoFactory
    {
        return new StashResponseDtoFactory(
            new AttachmentResponseDtoFactory(),
            $linkResolver ?? $this->createStub(LinkResolver::class),
        );
    }

    public function testCreateReturnsAttachments(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'doc.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 512;
        $attachment->path = 'attachments/doc.pdf';

        $stash = new Stash(StashType::File);

        $linkResolver = $this->createMock(LinkResolver::class);
        $linkResolver->expects($this->once())
            ->method('resolve')
            ->with($stash->getRef(), LinkKind::Ownership, Attachment::class)
            ->willReturn([$attachment]);

        $dto = $this->makeFactory($linkResolver)->create($stash);

        $this->assertInstanceOf(StashResponseDto::class, $dto);
        $this->assertCount(1, $dto->attachments);
        $this->assertEquals($attachment->id, $dto->attachments[0]->id);
        $this->assertEquals(AttachmentStatus::Pending, $dto->attachments[0]->status);
    }

    public function testCreateReturnsNullAttachmentsWhenEmpty(): void
    {
        $stash = new Stash(StashType::Text);

        $linkResolver = $this->createMock(LinkResolver::class);
        $linkResolver->expects($this->once())->method('resolve')->willReturn([]);

        $dto = $this->makeFactory($linkResolver)->create($stash);

        $this->assertNull($dto->attachments);
    }
}
