<?php

namespace App\Tests\Unit\Component\WebSocket\Message;

use App\Component\WebSocket\Message\WebSocketOptions;
use PHPUnit\Framework\TestCase;

class WebSocketOptionsTest extends TestCase
{
    public function testConstructorStoresChannel(): void
    {
        $options = new WebSocketOptions('test-channel');

        $this->assertEquals('test-channel', $options->getChannel());
    }

    public function testConstructorStoresChannelAndData(): void
    {
        $data = ['key' => 'value'];
        $options = new WebSocketOptions('test-channel', $data);

        $this->assertEquals('test-channel', $options->getChannel());
        $this->assertEquals($data, $options->getData());
    }

    public function testConstructorWithEmptyData(): void
    {
        $options = new WebSocketOptions('test-channel', []);

        $this->assertEquals([], $options->getData());
        $this->assertEquals([], $options->toArray());
    }

    public function testGetRecipientIdReturnsChannel(): void
    {
        $options = new WebSocketOptions('test-channel');

        $this->assertEquals('test-channel', $options->getRecipientId());
    }

    public function testToArrayReturnsData(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $options = new WebSocketOptions('channel', $data);

        $this->assertEquals($data, $options->toArray());
    }

    public function testDataMethodUpdatesData(): void
    {
        $options = new WebSocketOptions('channel');
        $newData = ['updated' => 'data'];

        $result = $options->data($newData);

        $this->assertEquals($newData, $options->getData());
    }

    public function testDataMethodReturnsInstance(): void
    {
        $options = new WebSocketOptions('channel');

        $result = $options->data(['key' => 'value']);

        $this->assertSame($options, $result);
    }

    public function testDataMethodAllowsChaining(): void
    {
        $options = new WebSocketOptions('channel');

        $result = $options->data(['key1' => 'value1'])
            ->data(['key2' => 'value2']);

        $this->assertSame($options, $result);
        $this->assertEquals(['key2' => 'value2'], $options->getData());
    }

    public function testComplexDataStructure(): void
    {
        $complexData = [
            'notification' => [
                'title' => 'New Task',
                'body' => 'You have a new task',
            ],
            'metadata' => [
                'taskId' => '123-456',
                'timestamp' => time(),
            ],
            'actions' => ['view', 'dismiss'],
        ];

        $options = new WebSocketOptions('notifications-channel', $complexData);

        $this->assertEquals($complexData, $options->toArray());
        $this->assertEquals($complexData, $options->getData());
    }

    public function testChannelCanContainSlashes(): void
    {
        $channelName = 'users/123/notifications';
        $options = new WebSocketOptions($channelName);

        $this->assertEquals($channelName, $options->getChannel());
        $this->assertEquals($channelName, $options->getRecipientId());
    }

    public function testDataWithScalarValues(): void
    {
        $data = [
            'status' => 'active',
            'count' => 42,
            'enabled' => true,
            'score' => 3.14,
        ];

        $options = new WebSocketOptions('channel', $data);

        $this->assertEquals($data, $options->toArray());
    }

    public function testDataMethodOverwritesPreviousData(): void
    {
        $options = new WebSocketOptions('channel', ['original' => 'data']);

        $options->data(['new' => 'data']);

        $this->assertEquals(['new' => 'data'], $options->getData());
        $this->assertArrayNotHasKey('original', $options->getData());
    }

    public function testEmptyChannelName(): void
    {
        $options = new WebSocketOptions('');

        $this->assertEquals('', $options->getChannel());
        $this->assertEquals('', $options->getRecipientId());
    }
}
