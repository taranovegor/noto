<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Link\CreateLinkDto;
use App\Enum\LinkKind;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CreateLinkDtoTest extends TestCase
{
    public function testCreateDto(): void
    {
        $sourceId = Uuid::v7();
        $targetId = Uuid::v7();

        $dto = new CreateLinkDto($sourceId, $targetId, LinkKind::Reference);

        $this->assertEquals($sourceId, $dto->sourceId);
        $this->assertEquals($targetId, $dto->targetId);
        $this->assertEquals(LinkKind::Reference, $dto->kind);
    }
}
