<?php

namespace App\Tests\Unit\Entity;

use App\Entity\RefreshToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RefreshTokenTest extends TestCase
{
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
    }

    public function testCreateForUserWithTtlGeneratesToken(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $tokenString = 'test_refresh_token';
        $ttl = 3600;

        $token = RefreshToken::createForUserWithTtl($tokenString, $user, $ttl);

        $this->assertInstanceOf(RefreshToken::class, $token);
        $this->assertEquals($tokenString, $token->getRefreshToken());
        $this->assertEquals($user->getUserIdentifier(), $token->getUsername());
    }

    public function testCreateForUserWithTtlSetsExpiration(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $ttl = 3600;

        $beforeCreation = new \DateTimeImmutable();
        $token = RefreshToken::createForUserWithTtl('token', $user, $ttl);
        $afterCreation = new \DateTimeImmutable();

        $valid = $token->getValid();

        // Token should be valid in the future
        $this->assertGreaterThan($afterCreation, $valid);
    }

    public function testIsValidReturnsTrueForFutureExpiration(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $token = RefreshToken::createForUserWithTtl('token', $user, 3600);

        $this->assertTrue($token->isValid());
    }

    public function testIsValidReturnsFalseForPastExpiration(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $token = RefreshToken::createForUserWithTtl('token', $user, -1);

        $this->assertFalse($token->isValid());
    }

    public function testSetRefreshTokenUpdatesToken(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $token = RefreshToken::createForUserWithTtl('initial', $user, 3600);

        $token->setRefreshToken('updated_token');

        $this->assertEquals('updated_token', $token->getRefreshToken());
    }

    public function testSetUsernameUpdatesUsername(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $token = RefreshToken::createForUserWithTtl('token', $user, 3600);

        $token->setUsername('newuser@example.com');

        $this->assertEquals('newuser@example.com', $token->getUsername());
    }

    public function testSetValidUpdatesExpirationTime(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $token = RefreshToken::createForUserWithTtl('token', $user, 3600);

        $newExpiration = new \DateTime('+7200 seconds');
        $token->setValid($newExpiration);

        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getValid());
    }

    public function testToStringReturnsTokenWhenSet(): void
    {
        $user = new User('test@example.com', 'password', $this->passwordHasher);
        $tokenString = 'refresh_token_value';
        $token = RefreshToken::createForUserWithTtl($tokenString, $user, 3600);

        $this->assertEquals($tokenString, (string) $token);
    }

    public function testToStringReturnsEmptyStringWhenTokenIsNull(): void
    {
        $token = new RefreshToken('user@example.com', '', new \DateTimeImmutable());

        $this->assertEquals('', (string) $token);
    }

    public function testToStringReturnsEmptyStringWhenTokenIsZero(): void
    {
        $token = new RefreshToken('user@example.com', '0', new \DateTimeImmutable());

        $this->assertEquals('', (string) $token);
    }
}
