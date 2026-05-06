<?php

namespace App\Tests\Unit\Factory\Link;

use App\Dto\Link\LinkResponseDto;
use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Factory\Link\LinkResponseDtoFactory;
use PHPUnit\Framework\TestCase;

class LinkResponseDtoFactoryTest extends TestCase
{
    private LinkResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new LinkResponseDtoFactory();
    }

    public function testCreateConvertsLinkToDto(): void
    {
        $source = new Ref(RefType::Task);
        $target = new Ref(RefType::Note);
        $link = new Link($source, $target, LinkKind::Ownership);

        $dto = $this->factory->create($link);

        $this->assertInstanceOf(LinkResponseDto::class, $dto);
        $this->assertEquals($link->id, $dto->id);
        $this->assertEquals($source->id, $dto->sourceId);
        $this->assertEquals(RefType::Task, $dto->sourceType);
        $this->assertEquals($target->id, $dto->targetId);
        $this->assertEquals(RefType::Note, $dto->targetType);
        $this->assertEquals(LinkKind::Ownership, $dto->kind);
        $this->assertEquals($link->createdAt, $dto->createdAt);
    }
}
