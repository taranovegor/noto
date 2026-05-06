<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Attachment\AttachmentDto;
use App\Dto\Stash\CreateStashDto;
use App\Enum\StashType;
use PHPUnit\Framework\TestCase;

class CreateStashDtoTest extends TestCase
{
    public function testTextStash(): void
    {
        $dto = new CreateStashDto(
            type: StashType::Text,
            content: 'clipboard text',
        );

        $this->assertEquals(StashType::Text, $dto->type);
        $this->assertEquals('clipboard text', $dto->content);
        $this->assertNull($dto->attachments);
    }

    public function testFileStash(): void
    {
        $file = new AttachmentDto('photo.png', 'image/png', 2048);

        $dto = new CreateStashDto(
            type: StashType::File,
            attachments: [$file],
        );

        $this->assertEquals(StashType::File, $dto->type);
        $this->assertNull($dto->content);
        $this->assertCount(1, $dto->attachments);
    }
}
