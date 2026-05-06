<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Stash;
use App\Enum\RefType;
use App\Enum\StashType;
use PHPUnit\Framework\TestCase;

class StashTest extends TestCase
{
    public function testConstructorInitializesDefaults(): void
    {
        $stash = new Stash(StashType::Text);

        $this->assertNotNull($stash->id);
        $this->assertNotNull($stash->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $stash->createdAt);
        $this->assertEquals(StashType::Text, $stash->type);
        $this->assertNull($stash->content);
        $this->assertNull($stash->expiresAt);
        $this->assertFalse($stash->pinned);
        $this->assertEquals(RefType::Stash, $stash->getRef()->type);
    }
}
