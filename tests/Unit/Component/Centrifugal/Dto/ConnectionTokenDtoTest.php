<?php

namespace App\Tests\Unit\Component\Centrifugal\Dto;

use App\Component\Centrifugal\Dto\ConnectionTokenDto;
use PHPUnit\Framework\TestCase;

class ConnectionTokenDtoTest extends TestCase
{
    public function testConstructorStoresUserId(): void
    {
        $dto = new ConnectionTokenDto('user-123', 'token-abc');

        $this->assertEquals('user-123', $dto->userId);
    }

    public function testConstructorStoresToken(): void
    {
        $dto = new ConnectionTokenDto('user-123', 'token-abc');

        $this->assertEquals('token-abc', $dto->token);
    }

    public function testConstructorStoresBothValues(): void
    {
        $userId = 'test-user';
        $token = 'connection-token-xyz';

        $dto = new ConnectionTokenDto($userId, $token);

        $this->assertEquals($userId, $dto->userId);
        $this->assertEquals($token, $dto->token);
    }

    public function testDtoPropertiesArePublic(): void
    {
        $dto = new ConnectionTokenDto('user-id', 'token');

        $this->assertTrue(property_exists($dto, 'userId'));
        $this->assertTrue(property_exists($dto, 'token'));
    }

    public function testDtoCanBeCreatedWithEmptyStrings(): void
    {
        $dto = new ConnectionTokenDto('', '');

        $this->assertEquals('', $dto->userId);
        $this->assertEquals('', $dto->token);
    }

    public function testDtoCanBeCreatedWithLongStrings(): void
    {
        $longUserId = str_repeat('a', 1000);
        $longToken = str_repeat('x', 5000);

        $dto = new ConnectionTokenDto($longUserId, $longToken);

        $this->assertEquals($longUserId, $dto->userId);
        $this->assertEquals($longToken, $dto->token);
    }

    public function testDtoCanBeCreatedWithSpecialCharacters(): void
    {
        $userId = 'user@example.com:with-special-chars';
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ';

        $dto = new ConnectionTokenDto($userId, $token);

        $this->assertEquals($userId, $dto->userId);
        $this->assertEquals($token, $dto->token);
    }

    public function testMultipleInstancesHaveIndependentValues(): void
    {
        $dto1 = new ConnectionTokenDto('user-1', 'token-1');
        $dto2 = new ConnectionTokenDto('user-2', 'token-2');

        $this->assertNotEquals($dto1->userId, $dto2->userId);
        $this->assertNotEquals($dto1->token, $dto2->token);
    }

    public function testDtoPropertiesCannotBeModified(): void
    {
        $dto = new ConnectionTokenDto('user-123', 'token-abc');

        // Verify that attempting to modify a readonly property throws an error
        try {
            $dto->userId = 'new-user';
            $this->fail('Expected error when modifying readonly property');
        } catch (\Error $e) {
            $this->assertStringContainsString('Cannot modify readonly property', $e->getMessage());
        }
    }

    public function testDtoCanBeSerializedToArray(): void
    {
        $dto = new ConnectionTokenDto('test-user', 'test-token');

        $array = [
            'userId' => $dto->userId,
            'token' => $dto->token,
        ];

        $this->assertEquals(['userId' => 'test-user', 'token' => 'test-token'], $array);
    }
}
