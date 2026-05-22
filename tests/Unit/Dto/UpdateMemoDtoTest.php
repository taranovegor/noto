<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Memo\UpdateMemoDto;
use PHPUnit\Framework\TestCase;

class UpdateMemoDtoTest extends TestCase
{
    public function testUpdateMemoDtoWithContent(): void
    {
        $dto = new UpdateMemoDto(
            content: 'Updated Content',
        );

        $this->assertEquals('Updated Content', $dto->content);
    }

    public function testUpdateMemoDtoWithNullContent(): void
    {
        $dto = new UpdateMemoDto(content: null);

        $this->assertNull($dto->content);
    }

    public function testUpdateMemoDtoWithoutContent(): void
    {
        $dto = new UpdateMemoDto();

        $this->assertNull($dto->content);
    }

    public function testUpdateMemoDtoIsReadonly(): void
    {
        $dto = new UpdateMemoDto(content: 'Test');

        $this->expectException(\Error::class);
        $dto->content = 'Modified';
    }
}
