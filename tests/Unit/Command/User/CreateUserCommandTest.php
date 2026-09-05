<?php

namespace App\Tests\Unit\Command\User;

use App\Command\User\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Flusher;
use App\Service\User\UserManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends TestCase
{
    private function makeCommand(UserRepository $repository, ?Flusher $flusher = null): CommandTester
    {
        $userManager = new UserManager($repository, $flusher ?? $this->createStub(Flusher::class));

        return new CommandTester(new CreateUserCommand($userManager));
    }

    public function testCreatesUserForNewEmail(): void
    {
        $email = 'new@example.com';

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('add');

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $exitCode = $this->makeCommand($repository, $flusher)->execute(['email' => $email]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testFailsWhenUserAlreadyExists(): void
    {
        $email = 'existing@example.com';
        $existingUser = new User($email);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);
        $repository->expects($this->never())->method('add');

        $exitCode = $this->makeCommand($repository)->execute(['email' => $email]);

        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
