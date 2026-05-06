<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Enum\RefType;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    public function testConstructorInitializesDefaults(): void
    {
        $attachment = new Attachment();

        $this->assertNotNull($attachment->id);
        $this->assertNotNull($attachment->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $attachment->createdAt);
        $this->assertEquals(RefType::Attachment, $attachment->getRef()->type);
        $this->assertEquals(AttachmentStatus::Pending, $attachment->status);
    }
}
