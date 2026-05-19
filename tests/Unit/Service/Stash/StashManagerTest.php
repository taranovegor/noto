<?php

namespace App\Tests\Unit\Service\Stash;

use App\Dto\Stash\CreateStashDto;
use App\Dto\Stash\UpdateStashDto;
use App\Entity\Attachment;
use App\Entity\Ref;
use App\Entity\Stash;
use App\Enum\LinkKind;
use App\Enum\StashType;
use App\Repository\StashRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use App\Service\Stash\StashManager;
use PHPUnit\Framework\TestCase;

class StashManagerTest extends TestCase
{
    private function makeManager(
        ?StashRepository $repo = null,
        ?LinkerInterface $linker = null,
        ?Flusher $flusher = null,
        ?\DateInterval $ttl = null,
    ): StashManager {
        return new StashManager(
            $repo ?? $this->createStub(StashRepository::class),
            $linker ?? $this->createStub(LinkerInterface::class),
            $flusher ?? $this->createStub(Flusher::class),
            $ttl ?? new \DateInterval('PT23H59M59S'),
        );
    }

    public function testCreateTextStash(): void
    {
        $repo = $this->createMock(StashRepository::class);
        $flusher = $this->createMock(Flusher::class);

        $repo->expects($this->once())->method('add');
        $flusher->expects($this->once())->method('flush');

        $stash = $this->makeManager(repo: $repo, flusher: $flusher)
            ->create(new CreateStashDto(type: StashType::Text, content: 'hello'));

        $this->assertInstanceOf(Stash::class, $stash);
        $this->assertEquals(StashType::Text, $stash->type);
        $this->assertEquals('hello', $stash->content);
        $this->assertNotNull($stash->expiresAt);
    }

    public function testCreateFileStashLinksAttachments(): void
    {
        $repo = $this->createMock(StashRepository::class);
        $linker = $this->createMock(LinkerInterface::class);
        $flusher = $this->createMock(Flusher::class);

        $a1 = new Attachment();
        $a2 = new Attachment();

        $repo->expects($this->once())->method('add');
        $linker->expects($this->exactly(2))->method('link')
            ->with($this->isInstanceOf(Ref::class), $this->isInstanceOf(Ref::class), LinkKind::Ownership);
        $flusher->expects($this->once())->method('flush');

        $stash = $this->makeManager(repo: $repo, linker: $linker, flusher: $flusher)
            ->create(new CreateStashDto(type: StashType::File, attachments: [$a1, $a2]));

        $this->assertEquals(StashType::File, $stash->type);
    }

    public function testUpdatePinned(): void
    {
        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $stash = new Stash(StashType::Text);
        $this->assertFalse($stash->pinned);

        $this->makeManager(flusher: $flusher)->update($stash, new UpdateStashDto(pinned: true));

        $this->assertTrue($stash->pinned);
    }

    public function testUpdatePinnedToTrueRemovesExpiration(): void
    {
        $stash = new Stash(StashType::Text);
        $stash->expiresAt = new \DateTimeImmutable()->modify('+1 day');

        $this->makeManager()->update($stash, new UpdateStashDto(pinned: true));

        $this->assertNull($stash->expiresAt);
    }

    public function testUpdatePinnedToFalseAddsExpiration(): void
    {
        $ttl = new \DateInterval('P7D');

        $stash = new Stash(StashType::Text);
        $stash->pinned = true;
        $stash->expiresAt = null;

        $beforeUpdate = new \DateTimeImmutable();
        $this->makeManager(ttl: $ttl)->update($stash, new UpdateStashDto(pinned: false));
        $afterUpdate = new \DateTimeImmutable();

        $this->assertNotNull($stash->expiresAt);
        $this->assertGreaterThanOrEqual($beforeUpdate->add($ttl), $stash->expiresAt);
        $this->assertLessThanOrEqual($afterUpdate->add($ttl), $stash->expiresAt);
    }

    public function testUpdatePinnedWithSameValueDoesNotUpdate(): void
    {
        $stash = new Stash(StashType::Text);
        $originalExpiresAt = $stash->expiresAt;

        $this->makeManager()->update($stash, new UpdateStashDto(pinned: false));

        $this->assertFalse($stash->pinned);
        $this->assertEquals($originalExpiresAt, $stash->expiresAt);
    }

    public function testUpdatePinnedWithNullValueDoesNotUpdate(): void
    {
        $stash = new Stash(StashType::Text);
        $originalExpiresAt = $stash->expiresAt;

        $this->makeManager()->update($stash, new UpdateStashDto(pinned: null));

        $this->assertFalse($stash->pinned);
        $this->assertEquals($originalExpiresAt, $stash->expiresAt);
    }
}
