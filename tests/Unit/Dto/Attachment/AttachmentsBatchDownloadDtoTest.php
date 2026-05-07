<?php

namespace App\Tests\Unit\Dto\Attachment;

use App\Dto\Attachment\AttachmentsBatchDownloadDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AttachmentsBatchDownloadDtoTest extends TestCase
{
    public function testDtoHoldsIds(): void
    {
        $ids = [Uuid::v7(), Uuid::v7(), Uuid::v7()];

        $dto = new AttachmentsBatchDownloadDto($ids);

        $this->assertCount(3, $dto->ids);
        $this->assertSame($ids, $dto->ids);
    }
}
