<?php

namespace App\Tests\Unit\Component\WebPush\Recipient;

use App\Component\WebPush\Recipient\WebPushRecipient;
use App\Component\WebPush\Recipient\WebPushRecipientProvider;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class WebPushRecipientProviderTest extends TestCase
{
    private UserSubscriptionManagerInterface&MockObject $registry;
    private RequestStack $requestStack;
    private WebPushRecipientProvider $provider;
    private UserInterface $user;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(UserSubscriptionManagerInterface::class);
        $this->requestStack = $this->createStub(RequestStack::class);
        $this->provider = new WebPushRecipientProvider($this->registry, $this->requestStack);
        $this->user = $this->createStub(UserInterface::class);
    }

    private function makeSubscription(string $endpoint): UserSubscriptionInterface
    {
        return $this->createConfiguredStub(UserSubscriptionInterface::class, [
            'getEndpoint' => $endpoint,
        ]);
    }

    private function requestWithHeader(string $checksum): Request
    {
        $request = new Request();
        $request->headers->set(WebPushRecipientProvider::SOURCE_HEADER, $checksum);

        return $request;
    }

    // forUser

    public function testForUserReturnsEmptyWhenNoSubscriptions(): void
    {
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn([]);

        $result = iterator_to_array($this->provider->forUser($this->user));

        $this->assertEmpty($result);
    }

    public function testForUserReturnsAllSubscriptionsAsWebPushRecipients(): void
    {
        $sub1 = $this->makeSubscription('https://fcm.example.com/push/one');
        $sub2 = $this->makeSubscription('https://fcm.example.com/push/two');

        $this->registry->expects($this->once())
            ->method('findByUser')
            ->with($this->user)
            ->willReturn([$sub1, $sub2]);

        $result = iterator_to_array($this->provider->forUser($this->user));

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(WebPushRecipient::class, $result);
        $this->assertSame($sub1, $result[0]->getSubscription());
        $this->assertSame($sub2, $result[1]->getSubscription());
    }

    public function testForUserIgnoresSourceHeader(): void
    {
        $endpoint = 'https://fcm.example.com/push/one';
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($this->requestWithHeader(sprintf('%u', crc32($endpoint))));

        $sub = $this->makeSubscription($endpoint);
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$sub]);

        $result = iterator_to_array($this->provider->forUser($this->user));

        $this->assertCount(1, $result);
    }

    // forUserExceptCurrent — no header

    public function testForUserExceptCurrentReturnsAllWhenNoRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $sub1 = $this->makeSubscription('https://fcm.example.com/push/one');
        $sub2 = $this->makeSubscription('https://fcm.example.com/push/two');
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$sub1, $sub2]);

        $result = iterator_to_array($this->provider->forUserExceptCurrent($this->user));

        $this->assertCount(2, $result);
    }

    public function testForUserExceptCurrentReturnsAllWhenHeaderAbsent(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(new Request());

        $sub = $this->makeSubscription('https://fcm.example.com/push/one');
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$sub]);

        $result = iterator_to_array($this->provider->forUserExceptCurrent($this->user));

        $this->assertCount(1, $result);
    }

    // forUserExceptCurrent — with header

    public function testForUserExceptCurrentSkipsSubscriptionWithMatchingChecksum(): void
    {
        $sourceEndpoint = 'https://fcm.googleapis.com/push/source-device';
        $otherEndpoint = 'https://fcm.googleapis.com/push/other-device';
        $checksum = sprintf('%u', crc32($sourceEndpoint));

        $this->requestStack->method('getCurrentRequest')
            ->willReturn($this->requestWithHeader($checksum));

        $sourceSub = $this->makeSubscription($sourceEndpoint);
        $otherSub = $this->makeSubscription($otherEndpoint);
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$sourceSub, $otherSub]);

        $result = iterator_to_array($this->provider->forUserExceptCurrent($this->user));

        $this->assertCount(1, $result);
        $this->assertSame($otherSub, $result[0]->getSubscription());
    }

    public function testForUserExceptCurrentReturnsAllWhenChecksumDoesNotMatchAnyEndpoint(): void
    {
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($this->requestWithHeader('0'));

        $sub1 = $this->makeSubscription('https://fcm.example.com/push/one');
        $sub2 = $this->makeSubscription('https://fcm.example.com/push/two');
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$sub1, $sub2]);

        $result = iterator_to_array($this->provider->forUserExceptCurrent($this->user));

        $this->assertCount(2, $result);
    }

    public function testForUserExceptCurrentReturnsEmptyWhenOnlySubscriptionMatches(): void
    {
        $endpoint = 'https://fcm.example.com/push/only';
        $checksum = sprintf('%u', crc32($endpoint));

        $this->requestStack->method('getCurrentRequest')
            ->willReturn($this->requestWithHeader($checksum));

        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$this->makeSubscription($endpoint)]);

        $result = iterator_to_array($this->provider->forUserExceptCurrent($this->user));

        $this->assertEmpty($result);
    }

    public function testForUserExceptCurrentWrapsRemainingSubscriptionsAsWebPushRecipients(): void
    {
        $sourceEndpoint = 'https://fcm.example.com/push/source';
        $checksum = sprintf('%u', crc32($sourceEndpoint));

        $this->requestStack->method('getCurrentRequest')
            ->willReturn($this->requestWithHeader($checksum));

        $sourceSub = $this->makeSubscription($sourceEndpoint);
        $keepSub = $this->makeSubscription('https://fcm.example.com/push/keep');
        $this->registry->expects($this->once())
            ->method('findByUser')
            ->willReturn([$sourceSub, $keepSub]);

        $result = iterator_to_array($this->provider->forUserExceptCurrent($this->user));

        $this->assertContainsOnlyInstancesOf(WebPushRecipient::class, $result);
    }
}
