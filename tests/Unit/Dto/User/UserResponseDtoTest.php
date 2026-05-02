<?php

namespace App\Tests\Unit\Dto\User;

use App\Dto\User\UserResponseDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserResponseDtoTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $id = Uuid::v7();
        $email = 'test@example.com';

        $dto = new UserResponseDto($id, $email);

        $this->assertSame($id, $dto->id);
        $this->assertEquals($email, $dto->email);
    }


    public function testDtoWithDifferentEmails(): void
    {
        $id = Uuid::v7();
        $emails = ['alice@example.com', 'bob@example.com', 'charlie@example.com'];

        foreach ($emails as $email) {
            $dto = new UserResponseDto($id, $email);
            $this->assertEquals($email, $dto->email);
        }
    }

    public function testDtoWithDifferentUuids(): void
    {
        $email = 'test@example.com';
        $id1 = Uuid::v7();
        $id2 = Uuid::v7();
        $id3 = Uuid::v7();

        $dto1 = new UserResponseDto($id1, $email);
        $dto2 = new UserResponseDto($id2, $email);
        $dto3 = new UserResponseDto($id3, $email);

        $this->assertNotEquals($dto1->id->toRfc4122(), $dto2->id->toRfc4122());
        $this->assertNotEquals($dto2->id->toRfc4122(), $dto3->id->toRfc4122());
    }
}
