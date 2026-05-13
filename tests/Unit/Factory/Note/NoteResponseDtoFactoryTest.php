<?php

namespace App\Tests\Unit\Factory\Note;

use App\Dto\Note\NoteResponseDto;
use App\Entity\Note;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Factory\Note\NoteResponseDtoFactory;
use App\Service\Link\LinkResolver;
use PHPUnit\Framework\TestCase;

class NoteResponseDtoFactoryTest extends TestCase
{
    private NoteResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new NoteResponseDtoFactory(
            $this->createStub(LinkResolver::class),
            new AttachmentResponseDtoFactory(),
        );
    }

    public function testCreateConvertsNoteToDto(): void
    {
        $content = '# Test Note'."\n".'Test Content';
        $note = new Note($content);

        $dto = $this->factory->create($note);

        $this->assertInstanceOf(NoteResponseDto::class, $dto);
        $this->assertEquals($note->id, $dto->id);
        $this->assertEquals($content, $dto->content);
        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
        $this->assertNull($dto->attachments);
    }

    public function testCreateWithAttachmentsReturnsAttachmentDtos(): void
    {
        $note = new Note('# Note');
        $linkResolver = $this->createStub(LinkResolver::class);
        $linkResolver->method('resolve')->willReturn([]);

        $factory = new NoteResponseDtoFactory($linkResolver, new AttachmentResponseDtoFactory());
        $dto = $factory->create($note);

        $this->assertNull($dto->attachments);
    }

    public function testCreatePreservesTimestamps(): void
    {
        $note = new Note('Content');

        $dto = $this->factory->create($note);

        $this->assertEquals($note->createdAt, $dto->createdAt);
        $this->assertEquals($note->updatedAt, $dto->updatedAt);
    }
}
