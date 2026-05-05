<?php

namespace App\Tests\Unit\Component\Centrifugo\EventSubscriber;

use App\Component\Centrifugo\CentrifugoInterface;
use App\Component\Centrifugo\Dto\ConnectionTokenDto;
use App\Component\Centrifugo\EventSubscriber\AttachConnectionTokenOnAuthenticationSuccess;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AttachConnectionTokenOnAuthenticationSuccessTest extends TestCase
{
    private AttachConnectionTokenOnAuthenticationSuccess $subscriber;

    protected function setUp(): void
    {
        $stubCentrifugo = $this->createStub(CentrifugoInterface::class);
        $stubNormalizer = $this->createStub(NormalizerInterface::class);

        $this->subscriber = new AttachConnectionTokenOnAuthenticationSuccess(
            $stubCentrifugo,
            $stubNormalizer,
            'http://centrifugo:8000',
        );
    }

    public function testSubscriberImplementsEventSubscriberInterface(): void
    {
        $this->assertInstanceOf(\Symfony\Component\EventDispatcher\EventSubscriberInterface::class, $this->subscriber);
    }

    public function testGetSubscribedEventsReturnsAuthenticationSuccessEvent(): void
    {
        $subscribedEvents = $this->subscriber->getSubscribedEvents();

        $this->assertArrayHasKey(Events::AUTHENTICATION_SUCCESS, $subscribedEvents);
        $this->assertEquals('__invoke', $subscribedEvents[Events::AUTHENTICATION_SUCCESS]);
    }

    public function testInvokeAttachesConnectionTokenToResponse(): void
    {
        $mockCentrifugo = $this->createMock(CentrifugoInterface::class);
        $mockNormalizer = $this->createMock(NormalizerInterface::class);
        $subscriber = new AttachConnectionTokenOnAuthenticationSuccess($mockCentrifugo, $mockNormalizer, 'http://centrifugo:8000');

        $user = $this->createStub(UserInterface::class);

        $connectionToken = new ConnectionTokenDto('user-123', 'token-abc');

        $mockCentrifugo->expects($this->once())
            ->method('generateConnectionToken')
            ->with($user)
            ->willReturn($connectionToken);

        $mockNormalizer->expects($this->once())
            ->method('normalize')
            ->with($connectionToken)
            ->willReturn(['userId' => 'user-123', 'token' => 'token-abc']);

        $event = $this->createMock(AuthenticationSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['jwt' => 'jwt-token']);

        $event->expects($this->once())
            ->method('setData')
            ->with([
                'jwt' => 'jwt-token',
                'centrifugo' => ['userId' => 'user-123', 'token' => 'token-abc', 'url' => 'http://centrifugo:8000'],
            ]);

        $subscriber->__invoke($event);
    }

    public function testInvokeGeneratesConnectionToken(): void
    {
        $mockCentrifugo = $this->createMock(CentrifugoInterface::class);
        $mockNormalizer = $this->createMock(NormalizerInterface::class);
        $subscriber = new AttachConnectionTokenOnAuthenticationSuccess($mockCentrifugo, $mockNormalizer, 'http://centrifugo:8000');

        $user = $this->createStub(UserInterface::class);
        $connectionToken = new ConnectionTokenDto('user-456', 'token-xyz');

        $mockCentrifugo->expects($this->once())
            ->method('generateConnectionToken')
            ->with($user)
            ->willReturn($connectionToken);

        $mockNormalizer->expects($this->once())
            ->method('normalize')
            ->willReturn([]);

        $event = $this->createMock(AuthenticationSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $event->expects($this->once())
            ->method('setData');

        $subscriber->__invoke($event);
    }

    public function testInvokeNormalizesConnectionToken(): void
    {
        $mockCentrifugo = $this->createMock(CentrifugoInterface::class);
        $mockNormalizer = $this->createMock(NormalizerInterface::class);
        $subscriber = new AttachConnectionTokenOnAuthenticationSuccess($mockCentrifugo, $mockNormalizer, 'http://centrifugo:8000');

        $user = $this->createStub(UserInterface::class);
        $connectionToken = new ConnectionTokenDto('user-789', 'token-123');

        $mockCentrifugo->expects($this->once())
            ->method('generateConnectionToken')
            ->willReturn($connectionToken);

        $mockNormalizer->expects($this->once())
            ->method('normalize')
            ->with($connectionToken)
            ->willReturn(['normalized' => 'data']);

        $event = $this->createMock(AuthenticationSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $event->expects($this->once())
            ->method('setData');

        $subscriber->__invoke($event);
    }

    public function testInvokeMergesWithExistingData(): void
    {
        $mockCentrifugo = $this->createMock(CentrifugoInterface::class);
        $mockNormalizer = $this->createMock(NormalizerInterface::class);
        $subscriber = new AttachConnectionTokenOnAuthenticationSuccess($mockCentrifugo, $mockNormalizer, 'http://centrifugo:8000');

        $user = $this->createStub(UserInterface::class);
        $connectionToken = new ConnectionTokenDto('user-id', 'token');

        $mockCentrifugo->expects($this->once())
            ->method('generateConnectionToken')
            ->willReturn($connectionToken);

        $normalizedToken = ['userId' => 'user-id', 'token' => 'token'];

        $mockNormalizer->expects($this->once())
            ->method('normalize')
            ->willReturn($normalizedToken);

        $existingData = [
            'jwt' => 'jwt-token',
            'user' => ['id' => 'user-id', 'email' => 'user@example.com'],
        ];

        $event = $this->createMock(AuthenticationSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($existingData);

        $expectedData = array_merge($existingData, ['centrifugo' => array_merge($normalizedToken, ['url' => 'http://centrifugo:8000'])]);

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $subscriber->__invoke($event);
    }

    public function testInvokeHandlesEmptyEventData(): void
    {
        $mockCentrifugo = $this->createMock(CentrifugoInterface::class);
        $mockNormalizer = $this->createMock(NormalizerInterface::class);
        $subscriber = new AttachConnectionTokenOnAuthenticationSuccess($mockCentrifugo, $mockNormalizer, 'http://centrifugo:8000');

        $user = $this->createStub(UserInterface::class);
        $connectionToken = new ConnectionTokenDto('user', 'token');

        $mockCentrifugo->expects($this->once())
            ->method('generateConnectionToken')
            ->willReturn($connectionToken);

        $normalizedToken = ['userId' => 'user', 'token' => 'token'];

        $mockNormalizer->expects($this->once())
            ->method('normalize')
            ->willReturn($normalizedToken);

        $event = $this->createMock(AuthenticationSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $event->expects($this->once())
            ->method('setData')
            ->with(['centrifugo' => array_merge($normalizedToken, ['url' => 'http://centrifugo:8000'])]);

        $subscriber->__invoke($event);
    }

    public function testInvokeIsCallable(): void
    {
        $this->assertTrue(is_callable([$this->subscriber, '__invoke']));
    }

    public function testSubscriberIsReadonly(): void
    {
        $this->assertInstanceOf(\Symfony\Component\EventDispatcher\EventSubscriberInterface::class, $this->subscriber);

        $reflection = new \ReflectionClass($this->subscriber);
        $this->assertTrue($reflection->isReadonly());
    }

    public function testMultipleInvocationsWithDifferentUsers(): void
    {
        $mockCentrifugo = $this->createMock(CentrifugoInterface::class);
        $mockNormalizer = $this->createMock(NormalizerInterface::class);
        $subscriber = new AttachConnectionTokenOnAuthenticationSuccess($mockCentrifugo, $mockNormalizer, 'http://centrifugo:8000');

        $user1 = $this->createStub(UserInterface::class);
        $user2 = $this->createStub(UserInterface::class);

        $token1 = new ConnectionTokenDto('user-1', 'token-1');
        $token2 = new ConnectionTokenDto('user-2', 'token-2');

        $mockCentrifugo->expects($this->exactly(2))
            ->method('generateConnectionToken')
            ->willReturnOnConsecutiveCalls($token1, $token2);

        $mockNormalizer->expects($this->exactly(2))
            ->method('normalize')
            ->willReturnOnConsecutiveCalls(
                ['userId' => 'user-1', 'token' => 'token-1'],
                ['userId' => 'user-2', 'token' => 'token-2']
            );

        $event1 = $this->createMock(AuthenticationSuccessEvent::class);
        $event1->expects($this->once())->method('getUser')->willReturn($user1);
        $event1->expects($this->once())->method('getData')->willReturn([]);
        $event1->expects($this->once())->method('setData');

        $event2 = $this->createMock(AuthenticationSuccessEvent::class);
        $event2->expects($this->once())->method('getUser')->willReturn($user2);
        $event2->expects($this->once())->method('getData')->willReturn([]);
        $event2->expects($this->once())->method('setData');

        $subscriber->__invoke($event1);
        $subscriber->__invoke($event2);
    }
}
