<?php

namespace App\Tests\Unit\Service\User;

use App\Entity\User;
use App\Exception\EntityNotFoundException;
use App\Repository\UserRepository;
use App\Service\Flusher;
use App\Service\User\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testCreateUserPersistsAndFlushes(): void
    {
        $email = 'test@example.com';

        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->expects(self::once())
            ->method('add');

        $flusherMock = $this->createMock(Flusher::class);
        $flusherMock->expects(self::once())
            ->method('flush');

        $userManager = new UserManager(
            $repositoryMock,
            $flusherMock
        );

        $user = $userManager->create($email);

        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testGetByEmailReturnsUser(): void
    {
        $email = 'user@example.com';
        $user = $this->createStub(User::class);

        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $flusherStub = $this->createStub(Flusher::class);

        $userManager = new UserManager(
            $repositoryMock,
            $flusherStub
        );

        $result = $userManager->getByEmail($email);

        $this->assertSame($user, $result);
    }

    public function testGetByEmailThrowsExceptionWhenUserNotFound(): void
    {
        $email = 'nonexistent@example.com';

        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $flusherStub = $this->createStub(Flusher::class);

        $userManager = new UserManager(
            $repositoryMock,
            $flusherStub
        );

        $this->expectException(EntityNotFoundException::class);

        $userManager->getByEmail($email);
    }

    public function testGetByEmailExceptionContainsEmailInfo(): void
    {
        $email = 'missing@example.com';

        $repositoryMock = $this->createMock(UserRepository::class);
        $repositoryMock->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $flusherStub = $this->createStub(Flusher::class);

        $userManager = new UserManager(
            $repositoryMock,
            $flusherStub
        );

        try {
            $userManager->getByEmail($email);
        } catch (EntityNotFoundException $e) {
            $this->assertStringContainsString('missing@example.com', $e->getCriteria());
        }
    }
}
