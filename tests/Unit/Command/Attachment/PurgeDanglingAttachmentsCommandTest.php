<?php

namespace App\Tests\Unit\Command\Attachment;

use App\Command\Attachment\PurgeDanglingAttachmentsCommand;
use App\Entity\Attachment;
use App\Repository\AttachmentRepository;
use App\Service\Attachment\AttachmentManager;
use App\Service\Flusher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PurgeDanglingAttachmentsCommandTest extends TestCase
{
    private function makeCommand(
        ?AttachmentRepository $repo = null,
        ?AttachmentManager $manager = null,
        ?Flusher $flusher = null,
    ): CommandTester {
        return new CommandTester(new PurgeDanglingAttachmentsCommand(
            $repo ?? $this->createStub(AttachmentRepository::class),
            $manager ?? $this->createStub(AttachmentManager::class),
            $flusher ?? $this->createStub(Flusher::class),
        ));
    }

    public function testPurgeDeletesDanglingAttachments(): void
    {
        $a1 = new Attachment();
        $a2 = new Attachment();

        $repo = $this->createMock(AttachmentRepository::class);
        $manager = $this->createMock(AttachmentManager::class);
        $flusher = $this->createMock(Flusher::class);

        $repo->expects($this->once())->method('findDangling')->willReturn([$a1, $a2]);
        $manager->expects($this->exactly(2))->method('delete')->with($this->isInstanceOf(Attachment::class));
        $flusher->expects($this->once())->method('flush');

        $exitCode = $this->makeCommand($repo, $manager, $flusher)->execute(['olderThan' => 'PT1H']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testPurgeDoesNothingWhenNoDanglingAttachments(): void
    {
        $repo = $this->createMock(AttachmentRepository::class);
        $repo->expects($this->once())->method('findDangling')->willReturn([]);

        $exitCode = $this->makeCommand(repo: $repo)->execute(['olderThan' => 'P1D']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testPurgeFailsOnInvalidDateInterval(): void
    {
        $exitCode = $this->makeCommand()->execute(['olderThan' => 'not-an-interval']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testFindDanglingReceivesCorrectCutoff(): void
    {
        $before = new \DateTimeImmutable();

        $repo = $this->createMock(AttachmentRepository::class);
        $repo->expects($this->once())
            ->method('findDangling')
            ->with($this->callback(fn (\DateTimeImmutable $cutoff) => $cutoff <= $before))
            ->willReturn([]);

        $this->makeCommand(repo: $repo)->execute(['olderThan' => 'PT1H']);
    }
}
