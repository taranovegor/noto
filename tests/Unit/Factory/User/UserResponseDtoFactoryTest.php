<?php

namespace App\Tests\Unit\Factory\User;

use App\Dto\User\UserResponseDto;
use App\Entity\User;
use App\Factory\User\UserResponseDtoFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserResponseDtoFactoryTest extends TestCase
{
    private UserResponseDtoFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new UserResponseDtoFactory();
    }

    public function testCreateReturnsUserResponseDto(): void
    {
        $user = new User('test@example.com');

        $dto = $this->factory->create($user);

        $this->assertInstanceOf(UserResponseDto::class, $dto);
    }

    public function testCreatePreservesUserEmail(): void
    {
        $email = 'john@example.com';
        $user = new User($email);

        $dto = $this->factory->create($user);

        $this->assertEquals($email, $dto->email);
    }

    public function testCreatePreservesUserId(): void
    {
        $user = new User('test@example.com');

        $dto = $this->factory->create($user);

        $this->assertInstanceOf(Uuid::class, $dto->id);
        $this->assertEquals($user->id->toRfc4122(), $dto->id->toRfc4122());
    }

    public function testCreateWithDifferentEmails(): void
    {
        $emails = ['alice@example.com', 'bob@example.com', 'charlie@example.com'];

        foreach ($emails as $email) {
            $user = new User($email);
            $dto = $this->factory->create($user);

            $this->assertEquals($email, $dto->email);
        }
    }
}
