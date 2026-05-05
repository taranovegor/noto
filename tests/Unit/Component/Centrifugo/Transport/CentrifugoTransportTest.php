<?php

namespace App\Tests\Unit\Component\Centrifugo\Transport;

use App\Component\Centrifugo\CentrifugoInterface;
use App\Component\Centrifugo\Transport\CentrifugoTransport;
use App\Component\WebSocket\Exception\WebSocketTransportException;
use App\Component\WebSocket\Message\WebSocketOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

class CentrifugoTransportTest extends TestCase
{
    private MockObject|CentrifugoInterface $mockCentrifugo;
    private CentrifugoTransport $transport;

    protected function setUp(): void
    {
        $this->mockCentrifugo = $this->createStub(CentrifugoInterface::class);
        $this->transport = new CentrifugoTransport($this->mockCentrifugo);
    }

    public function testSupportsReturnsTrueForChatMessageWithNoOptions(): void
    {
        $message = new ChatMessage('Test subject');

        $this->assertTrue($this->transport->supports($message));
    }

    public function testSupportsReturnsTrueForChatMessageWithWebSocketOptions(): void
    {
        $options = new WebSocketOptions('test-channel');
        $message = new ChatMessage('Test subject', $options);

        $this->assertTrue($this->transport->supports($message));
    }

    public function testSupportsReturnsFalseForNonChatMessage(): void
    {
        $message = new SmsMessage('1234567890', 'Test message');

        $this->assertFalse($this->transport->supports($message));
    }

    public function testDoSendPublishesChatMessageWithWebSocketOptions(): void
    {
        $centrifugo = $this->createMock(CentrifugoInterface::class);
        $transport = new CentrifugoTransport($centrifugo);

        $options = new WebSocketOptions('test-channel');
        $message = new ChatMessage('Test subject', $options);

        $centrifugo->expects($this->once())
            ->method('publish')
            ->with(
                'test-channel',
                $this->callback(function ($data) {
                    return isset($data['meta']['subject']) && 'Test subject' === $data['meta']['subject']
                        && isset($data['meta']['id']);
                })
            );

        $sentMessage = $transport->send($message);

        $this->assertNotNull($sentMessage->getMessageId());
    }

    public function testDoSendPublishesChatMessageWithData(): void
    {
        $centrifugo = $this->createMock(CentrifugoInterface::class);
        $transport = new CentrifugoTransport($centrifugo);

        $options = new WebSocketOptions('test-channel', ['custom' => 'data']);
        $message = new ChatMessage('Test subject', $options);

        $centrifugo->expects($this->once())
            ->method('publish')
            ->with(
                'test-channel',
                $this->callback(function ($data) {
                    return isset($data['meta']['subject'])
                        && isset($data['meta']['id'])
                        && isset($data['data']['custom'])
                        && 'data' === $data['data']['custom'];
                })
            );

        $sentMessage = $transport->send($message);

        $this->assertNotNull($sentMessage->getMessageId());
    }

    public function testDoSendGeneratesUniqueMessageIds(): void
    {
        $centrifugo = $this->createMock(CentrifugoInterface::class);
        $transport = new CentrifugoTransport($centrifugo);

        $message1 = new ChatMessage('Subject 1', new WebSocketOptions('channel1'));
        $message2 = new ChatMessage('Subject 2', new WebSocketOptions('channel2'));

        $centrifugo->expects($this->exactly(2))
            ->method('publish');

        $sentMessage1 = $transport->send($message1);
        $sentMessage2 = $transport->send($message2);

        $this->assertNotEquals($sentMessage1->getMessageId(), $sentMessage2->getMessageId());
    }

    public function testDoSendThrowsExceptionOnUnsupportedMessage(): void
    {
        $message = new SmsMessage('1234567890', 'Test message');

        $this->expectException(UnsupportedMessageTypeException::class);
        $this->transport->send($message);
    }

    public function testDoSendThrowsWebSocketTransportExceptionOnPublishFailure(): void
    {
        $centrifugo = $this->createMock(CentrifugoInterface::class);
        $transport = new CentrifugoTransport($centrifugo);

        $options = new WebSocketOptions('test-channel');
        $message = new ChatMessage('Test subject', $options);

        $centrifugo->expects($this->once())
            ->method('publish')
            ->willThrowException(new \RuntimeException('Publish failed'));

        $this->expectException(WebSocketTransportException::class);
        $transport->send($message);
    }

    public function testToStringReturnsValidSchemeAndEndpoint(): void
    {
        $stringRepresentation = (string) $this->transport;

        $this->assertStringStartsWith('centrifugo://', $stringRepresentation);
    }

    public function testDoSendWithComplexDataInOptions(): void
    {
        $centrifugo = $this->createMock(CentrifugoInterface::class);
        $transport = new CentrifugoTransport($centrifugo);

        $complexData = [
            'notification' => [
                'title' => 'Task Created',
                'description' => 'A new task has been created',
            ],
            'metadata' => [
                'taskId' => '123',
                'projectId' => '456',
            ],
        ];
        $options = new WebSocketOptions('tasks-channel', $complexData);
        $message = new ChatMessage('Task notification', $options);

        $centrifugo->expects($this->once())
            ->method('publish')
            ->with(
                'tasks-channel',
                $this->callback(function ($data) use ($complexData) {
                    foreach ($complexData as $key => $value) {
                        if (!isset($data['data'][$key]) || $data['data'][$key] !== $value) {
                            return false;
                        }
                    }

                    return true;
                })
            );

        $transport->send($message);
    }

    public function testDoSendMergesSubjectAndIdWithOptionsData(): void
    {
        $centrifugo = $this->createMock(CentrifugoInterface::class);
        $transport = new CentrifugoTransport($centrifugo);

        $options = new WebSocketOptions('channel', ['existing' => 'data']);
        $message = new ChatMessage('Message Subject', $options);

        $centrifugo->expects($this->once())
            ->method('publish')
            ->with(
                'channel',
                $this->callback(function ($data) {
                    return isset($data['meta']['subject']) && 'Message Subject' === $data['meta']['subject']
                        && isset($data['meta']['id'])
                        && isset($data['data']['existing']) && 'data' === $data['data']['existing'];
                })
            );

        $transport->send($message);
    }
}
