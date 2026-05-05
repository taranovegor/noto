<?php

namespace App\Tests\Unit\Component\Centrifugo\Service;

use App\Component\Centrifugo\Service\UserIdNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class UserIdNormalizerTest extends TestCase
{
    private UserIdNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new UserIdNormalizer();
    }

    public function testNormalizeReturnsValidMd5Hash(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test-user');

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5('test-user'), $result);
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
    }

    public function testNormalizeReturnsMd5OfUserIdentifier(): void
    {
        $user = $this->createMock(UserInterface::class);
        $identifier = 'user@example.com';
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($identifier);

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5($identifier), $result);
    }

    public function testNormalizeDifferentUsersReturnDifferentHashes(): void
    {
        $user1 = $this->createMock(UserInterface::class);
        $user1->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user-1');

        $user2 = $this->createMock(UserInterface::class);
        $user2->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('user-2');

        $result1 = $this->normalizer->normalize($user1);
        $result2 = $this->normalizer->normalize($user2);

        $this->assertNotEquals($result1, $result2);
    }

    public function testNormalizeSameUserReturnsSameHash(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->exactly(2))
            ->method('getUserIdentifier')
            ->willReturn('consistent-user');

        $result1 = $this->normalizer->normalize($user);
        $result2 = $this->normalizer->normalize($user);

        $this->assertEquals($result1, $result2);
    }

    public function testNormalizeWithEmptyIdentifier(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('');

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5(''), $result);
    }

    public function testNormalizeWithNumericIdentifier(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('12345');

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5('12345'), $result);
    }

    public function testNormalizeWithSpecialCharacters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $identifier = 'user@example.com!#$%^&*()';
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($identifier);

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5($identifier), $result);
    }

    public function testNormalizeWithUnicodeCharacters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $identifier = 'пользователь';
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($identifier);

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5($identifier), $result);
    }

    public function testNormalizeWithLongIdentifier(): void
    {
        $user = $this->createMock(UserInterface::class);
        $longIdentifier = str_repeat('a', 1000);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($longIdentifier);

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5($longIdentifier), $result);
        $this->assertEquals(32, strlen($result));
    }

    public function testNormalizeReturnsLowercaseHexString(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('TestUser');

        $result = $this->normalizer->normalize($user);

        $this->assertEquals(md5('TestUser'), $result);
        $this->assertTrue(ctype_xdigit($result));
    }
}
