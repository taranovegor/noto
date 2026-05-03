<?php

namespace App\Tests\Unit\Component\Centrifugal\Builder;

use App\Component\Centrifugal\Builder\CentrifugalChannelBuilder;
use PHPUnit\Framework\TestCase;

class CentrifugalChannelBuilderTest extends TestCase
{
    private CentrifugalChannelBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CentrifugalChannelBuilder();
    }

    public function testBuildSimplePublicChannel(): void
    {
        $result = $this->builder->channel('notifications')->build();

        $this->assertEquals('notifications', $result);
    }

    public function testBuildPrivateChannel(): void
    {
        $result = $this->builder->private()->channel('messages')->build();

        $this->assertEquals('$messages', $result);
    }

    public function testBuildChannelWithNamespace(): void
    {
        $result = $this->builder
            ->namespace('tasks')
            ->channel('updates')
            ->build();

        $this->assertEquals('tasks:updates', $result);
    }

    public function testBuildPrivateChannelWithNamespace(): void
    {
        $result = $this->builder
            ->private()
            ->namespace('users')
            ->channel('profile')
            ->build();

        $this->assertEquals('$users:profile', $result);
    }

    public function testBuildChannelForSingleUser(): void
    {
        $result = $this->builder
            ->channel('notifications')
            ->forUser(123)
            ->build();

        $this->assertEquals('notifications#123', $result);
    }

    public function testBuildChannelForMultipleUsers(): void
    {
        $result = $this->builder
            ->channel('notifications')
            ->forUser(123, 456, 789)
            ->build();

        $this->assertEquals('notifications#123,456,789', $result);
    }

    public function testBuildCompleteChannel(): void
    {
        $result = $this->builder
            ->private()
            ->namespace('app')
            ->channel('events')
            ->forUser(1, 2, 3)
            ->build();

        $this->assertEquals('$app:events#1,2,3', $result);
    }

    public function testBuildThrowsExceptionWhenChannelNameMissing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Channel name is required');

        $this->builder->build();
    }

    public function testValidateThrowsExceptionForInvalidNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid namespace name');

        $this->builder
            ->namespace('a')
            ->channel('test')
            ->build();
    }

    public function testValidateThrowsExceptionForNamespaceWithSpecialCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid namespace name');

        $this->builder
            ->namespace('invalid:name')
            ->channel('test')
            ->build();
    }

    public function testValidateThrowsExceptionForChannelWithReservedCharacters(): void
    {
        $reservedSymbols = [':', '#', '$', '/', '*', '&'];

        foreach ($reservedSymbols as $symbol) {
            $builder = new CentrifugalChannelBuilder();
            $channelName = 'test'.$symbol.'channel';

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Channel name must not contain reserved symbol');

            $builder->channel($channelName)->build();
        }
    }

    public function testValidateThrowsExceptionForNonAsciiChannelName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Channel name must contain only ASCII characters');

        $this->builder->channel('тестовый')->build();
    }

    public function testValidateThrowsExceptionForChannelExceeding255Characters(): void
    {
        $longName = str_repeat('a', 250);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Channel name exceeds 255 characters');

        $this->builder
            ->namespace('very-long-namespace')
            ->channel($longName)
            ->forUser(1, 2, 3)
            ->build();
    }

    public function testValidNamespaceMinimumLength(): void
    {
        $result = $this->builder
            ->namespace('ab')
            ->channel('test')
            ->build();

        $this->assertEquals('ab:test', $result);
    }

    public function testValidNamespaceWithNumbers(): void
    {
        $result = $this->builder
            ->namespace('app123')
            ->channel('events')
            ->build();

        $this->assertEquals('app123:events', $result);
    }

    public function testValidNamespaceWithUnderscore(): void
    {
        $result = $this->builder
            ->namespace('app_name')
            ->channel('events')
            ->build();

        $this->assertEquals('app_name:events', $result);
    }

    public function testValidNamespaceWithHyphen(): void
    {
        $result = $this->builder
            ->namespace('app-name')
            ->channel('events')
            ->build();

        $this->assertEquals('app-name:events', $result);
    }

    public function testSwitchFromPrivateToPublic(): void
    {
        $result = $this->builder
            ->private()
            ->public()
            ->channel('test')
            ->build();

        $this->assertEquals('test', $result);
    }

    public function testSwitchFromPublicToPrivate(): void
    {
        $result = $this->builder
            ->public()
            ->private()
            ->channel('test')
            ->build();

        $this->assertEquals('$test', $result);
    }

    public function testResetClearsAllProperties(): void
    {
        $this->builder
            ->private()
            ->namespace('app')
            ->channel('events')
            ->forUser(1, 2, 3);

        $this->builder->reset();

        $this->expectException(\LogicException::class);
        $this->builder->build();
    }

    public function testBuilderChaining(): void
    {
        $result = $this->builder
            ->namespace('chat')
            ->private()
            ->channel('messages')
            ->forUser(123)
            ->build();

        $this->assertEquals('$chat:messages#123', $result);
    }

    public function testForUserWithStringIds(): void
    {
        $result = $this->builder
            ->channel('notifications')
            ->forUser('user-1', 'user-2')
            ->build();

        $this->assertEquals('notifications#user-1,user-2', $result);
    }

    public function testForUserWithMixedIds(): void
    {
        $result = $this->builder
            ->channel('notifications')
            ->forUser(123, 'user-abc')
            ->build();

        $this->assertEquals('notifications#123,user-abc', $result);
    }

    public function testForUserReplacesPreviousIds(): void
    {
        $result = $this->builder
            ->channel('notifications')
            ->forUser(1, 2, 3)
            ->forUser(4, 5)
            ->build();

        $this->assertEquals('notifications#4,5', $result);
    }

    public function testEmptyChannelNameAfterResetRequiresBuild(): void
    {
        $this->builder->channel('test');
        $this->builder->reset();

        $this->expectException(\LogicException::class);
        $this->builder->build();
    }
}
