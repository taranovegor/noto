<?php

namespace App\Tests\Unit\Command\Stash;

use App\Command\Stash\PruneStashCommand;
use App\Entity\Stash;
use App\Enum\StashType;
use App\Repository\StashRepository;
use App\Service\Flusher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PruneStashCommandTest extends TestCase
{
    public function testExecuteRemovesExpiredStashes(): void
    {
        $expired = new Stash(StashType::Text);
        $expired->expiresAt = new \DateTimeImmutable('-10 days');

        $repository = $this->createMock(StashRepository::class);
        $repository->expects($this->once())
            ->method('findExpired')
            ->willReturn([$expired]);
        $repository->expects($this->once())->method('remove')->with($expired);

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $command = new PruneStashCommand($repository, $flusher);
        $tester = new CommandTester($command);

        $tester->execute(['grace' => 'P7D']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Removed 1', $tester->getDisplay());
    }

    public function testExecuteNoExpiredStashes(): void
    {
        $repository = $this->createMock(StashRepository::class);
        $repository->expects($this->once())
            ->method('findExpired')
            ->willReturn([]);

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->never())->method('flush');

        $command = new PruneStashCommand($repository, $flusher);
        $tester = new CommandTester($command);

        $tester->execute(['grace' => 'P7D']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('No expired stashes', $tester->getDisplay());
    }

    public function testExecuteInvalidDateInterval(): void
    {
        $repository = $this->createStub(StashRepository::class);
        $flusher = $this->createStub(Flusher::class);

        $command = new PruneStashCommand($repository, $flusher);
        $tester = new CommandTester($command);

        $tester->execute(['grace' => 'INVALID']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Invalid DateInterval', $tester->getDisplay());
    }
}
