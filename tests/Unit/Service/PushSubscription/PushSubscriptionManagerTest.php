<?php

namespace App\Tests\Unit\Service\PushSubscription;

use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use App\Service\Flusher;
use App\Service\PushSubscription\PushSubscriptionManager;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class PushSubscriptionManagerTest extends TestCase
{
    private function makeManager(
        ?PushSubscriptionRepository $repository = null,
        ?Flusher $flusher = null,
    ): PushSubscriptionManager {
        return new PushSubscriptionManager(
            $repository ?? $this->createStub(PushSubscriptionRepository::class),
            $flusher ?? $this->createStub(Flusher::class),
        );
    }

    private function makeUser(string $identifier = 'user@example.com'): User
    {
        return $this->createConfiguredStub(User::class, ['getUserIdentifier' => $identifier]);
    }

    private function makePushSubscription(): PushSubscription
    {
        return new PushSubscription(
            $this->makeUser(),
            'hash123',
            ['endpoint' => 'https://fcm.example.com/push/abc', 'keys' => ['p256dh' => 'key', 'auth' => 'auth']],
        );
    }

    // hash

    public function testHashReturnsSha256OfEndpointAndUserIdentifier(): void
    {
        $endpoint = 'https://fcm.googleapis.com/push/abc123';
        $identifier = 'user@example.com';
        $user = $this->createConfiguredStub(UserInterface::class, ['getUserIdentifier' => $identifier]);

        $result = $this->makeManager()->hash($endpoint, $user);

        $this->assertSame(hash('sha256', $endpoint.$identifier), $result);
    }

    public function testHashDiffersForDifferentUsers(): void
    {
        $endpoint = 'https://fcm.googleapis.com/push/abc123';
        $user1 = $this->createConfiguredStub(UserInterface::class, ['getUserIdentifier' => 'alice@example.com']);
        $user2 = $this->createConfiguredStub(UserInterface::class, ['getUserIdentifier' => 'bob@example.com']);

        $manager = $this->makeManager();

        $this->assertNotSame($manager->hash($endpoint, $user1), $manager->hash($endpoint, $user2));
    }

    public function testHashDiffersForDifferentEndpoints(): void
    {
        $user = $this->createConfiguredStub(UserInterface::class, ['getUserIdentifier' => 'user@example.com']);
        $manager = $this->makeManager();

        $this->assertNotSame(
            $manager->hash('https://fcm.example.com/push/one', $user),
            $manager->hash('https://fcm.example.com/push/two', $user),
        );
    }

    // factory

    public function testFactoryReturnsPushSubscription(): void
    {
        $user = $this->makeUser();
        $subscriptionData = ['endpoint' => 'https://fcm.example.com/push/abc', 'keys' => ['p256dh' => 'key', 'auth' => 'auth']];

        $result = $this->makeManager()->factory($user, 'hash123', $subscriptionData);

        $this->assertInstanceOf(PushSubscription::class, $result);
        $this->assertSame($user, $result->user);
        $this->assertSame('hash123', $result->subscriptionHash);
        $this->assertSame($subscriptionData, $result->subscription);
    }

    // getUserSubscription

    public function testGetUserSubscriptionDelegatesToRepository(): void
    {
        $user = $this->makeUser();
        $subscription = $this->makePushSubscription();

        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())
            ->method('findOneByUserAndHash')
            ->with($user, 'hash123')
            ->willReturn($subscription);

        $result = $this->makeManager($repository)->getUserSubscription($user, 'hash123');

        $this->assertSame($subscription, $result);
    }

    public function testGetUserSubscriptionReturnsNullWhenNotFound(): void
    {
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())
            ->method('findOneByUserAndHash')
            ->willReturn(null);

        $result = $this->makeManager($repository)->getUserSubscription($this->makeUser(), 'nonexistent');

        $this->assertNull($result);
    }

    // findByUser

    public function testFindByUserDelegatesToRepository(): void
    {
        $user = $this->makeUser();
        $subscriptions = [$this->makePushSubscription(), $this->makePushSubscription()];

        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($subscriptions);

        $result = $this->makeManager($repository)->findByUser($user);

        $this->assertSame($subscriptions, $result);
    }

    // findByHash

    public function testFindByHashDelegatesToRepository(): void
    {
        $subscriptions = [$this->makePushSubscription()];

        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())
            ->method('findByHash')
            ->with('hash123')
            ->willReturn($subscriptions);

        $result = $this->makeManager($repository)->findByHash('hash123');

        $this->assertSame($subscriptions, $result);
    }

    // save

    public function testSaveCallsRepositoryAddAndFlushes(): void
    {
        $subscription = $this->makePushSubscription();

        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())->method('add')->with($subscription);

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager($repository, $flusher)->save($subscription);
    }

    public function testSaveThrowsWhenNotPushSubscription(): void
    {
        $this->expectException(\AssertionError::class);

        $this->makeManager()->save($this->createStub(UserSubscriptionInterface::class));
    }

    // delete

    public function testDeleteCallsRepositoryRemoveAndFlushes(): void
    {
        $subscription = $this->makePushSubscription();

        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())->method('remove')->with($subscription);

        $flusher = $this->createMock(Flusher::class);
        $flusher->expects($this->once())->method('flush');

        $this->makeManager($repository, $flusher)->delete($subscription);
    }

    public function testDeleteThrowsWhenNotPushSubscription(): void
    {
        $this->expectException(\AssertionError::class);

        $this->makeManager()->delete($this->createStub(UserSubscriptionInterface::class));
    }
}
