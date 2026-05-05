<?php

namespace App\Tests\Unit\Component\Centrifugo;

use App\Component\Centrifugo\Centrifugo;
use App\Component\Centrifugo\Dto\ConnectionTokenDto;
use App\Component\Centrifugo\Service\UserIdNormalizer;
use phpcent\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CentrifugoTest extends TestCase
{
    private MockObject|Client $mockClient;
    private MockObject|LoggerInterface $mockLogger;
    private UserIdNormalizer $userIdNormalizer;
    private Centrifugo $centrifugo;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->mockLogger = $this->createStub(LoggerInterface::class);
        $this->userIdNormalizer = new UserIdNormalizer();

        $this->centrifugo = new Centrifugo(
            $this->mockClient,
            $this->mockLogger,
            $this->userIdNormalizer,
            new \DateInterval('PT1H')
        );
    }

    public function testGenerateConnectionTokenWithDefaultTtl(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test-user');

        $normalizedUserId = md5('test-user');

        $this->mockClient->expects($this->once())
            ->method('generateConnectionToken')
            ->with(
                $normalizedUserId,
                $this->callback(fn ($arg) => is_int($arg)),
                ['identifier' => $normalizedUserId],
                channels: []
            )
            ->willReturn('test-token');

        $result = $this->centrifugo->generateConnectionToken($user);

        $this->assertInstanceOf(ConnectionTokenDto::class, $result);
        $this->assertEquals($normalizedUserId, $result->userId);
        $this->assertEquals('test-token', $result->token);
    }

    public function testGenerateConnectionTokenWithCustomTtl(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test-user');

        $customTtl = new \DateInterval('PT30M');
        $normalizedUserId = md5('test-user');

        $this->mockClient->expects($this->once())
            ->method('generateConnectionToken')
            ->with(
                $normalizedUserId,
                $this->callback(fn ($arg) => is_int($arg)),
                ['identifier' => $normalizedUserId],
                channels: []
            )
            ->willReturn('test-token-custom');

        $result = $this->centrifugo->generateConnectionToken($user, [], $customTtl);

        $this->assertInstanceOf(ConnectionTokenDto::class, $result);
        $this->assertEquals('test-token-custom', $result->token);
    }

    public function testGenerateConnectionTokenWithChannels(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test-user');

        $channels = ['channel1', 'channel2'];
        $normalizedUserId = md5('test-user');

        $this->mockClient->expects($this->once())
            ->method('generateConnectionToken')
            ->with(
                $normalizedUserId,
                $this->callback(fn ($arg) => is_int($arg)),
                ['identifier' => $normalizedUserId],
                channels: $channels
            )
            ->willReturn('test-token-channels');

        $result = $this->centrifugo->generateConnectionToken($user, $channels);

        $this->assertInstanceOf(ConnectionTokenDto::class, $result);
        $this->assertEquals('test-token-channels', $result->token);
    }

    public function testGenerateConnectionTokenLogsDebugMessage(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test-user');

        $mockLogger = $this->createMock(LoggerInterface::class);
        $centrifugo = new Centrifugo($this->mockClient, $mockLogger, $this->userIdNormalizer);

        $this->mockClient->expects($this->once())
            ->method('generateConnectionToken')
            ->willReturn('test-token');

        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Created web socket token'),
                $this->arrayHasKey('userId')
            );

        $centrifugo->generateConnectionToken($user);
    }

    public function testPublishData(): void
    {
        $channel = 'test-channel';
        $data = ['key' => 'value'];

        $this->mockClient->expects($this->once())
            ->method('publish')
            ->with($channel, $data);

        $this->centrifugo->publish($channel, $data);
    }

    public function testPublishLogsDebugMessage(): void
    {
        $channel = 'test-channel';
        $data = ['key' => 'value'];

        $mockLogger = $this->createMock(LoggerInterface::class);
        $centrifugo = new Centrifugo($this->mockClient, $mockLogger, $this->userIdNormalizer);

        $this->mockClient->expects($this->once())
            ->method('publish');

        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Published data into the channel'),
                $this->arrayHasKey('channel')
            );

        $centrifugo->publish($channel, $data);
    }

    public function testPublishWithComplexData(): void
    {
        $channel = 'notifications';
        $data = [
            'type' => 'task_created',
            'taskId' => 'uuid-123',
            'taskName' => 'Test Task',
            'timestamp' => time(),
        ];

        $this->mockClient->expects($this->once())
            ->method('publish')
            ->with($channel, $data);

        $this->centrifugo->publish($channel, $data);
    }

    public function testPublishEmptyData(): void
    {
        $channel = 'test-channel';
        $data = [];

        $this->mockClient->expects($this->once())
            ->method('publish')
            ->with($channel, $data);

        $this->centrifugo->publish($channel, $data);
    }
}
