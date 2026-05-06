<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Stash\UpdateStashDto;
use PHPUnit\Framework\TestCase;

class UpdateStashDtoTest extends TestCase
{
    public function testPinTrue(): void
    {
        $dto = new UpdateStashDto(pinned: true);

        $this->assertTrue($dto->pinned);
    }

    public function testNoChanges(): void
    {
        $dto = new UpdateStashDto();

        $this->assertNull($dto->pinned);
    }
}
