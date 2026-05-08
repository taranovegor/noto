<?php

namespace App\Service\PushSubscription;

use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use App\Service\Flusher;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Security\Core\User\UserInterface;

#[AutoconfigureTag('bentools_webpush.subscription_manager', ['user_class' => User::class])]
readonly class PushSubscriptionManager implements UserSubscriptionManagerInterface
{
    public function __construct(
        private PushSubscriptionRepository $repository,
        private Flusher $flusher,
    ) {
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $options
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription, array $options = []): PushSubscription
    {
        assert($user instanceof User);

        return new PushSubscription($user, $subscriptionHash, $subscription);
    }

    public function hash(string $endpoint, UserInterface $user): string
    {
        return hash('sha256', $endpoint.$user->getUserIdentifier());
    }

    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?PushSubscription
    {
        assert($user instanceof User);

        return $this->repository->findOneByUserAndHash($user, $subscriptionHash);
    }

    public function findByUser(UserInterface $user): iterable
    {
        assert($user instanceof User);

        return $this->repository->findByUser($user);
    }

    /**
     * @return iterable<PushSubscription>
     */
    public function findByHash(string $subscriptionHash): iterable
    {
        return $this->repository->findByHash($subscriptionHash);
    }

    public function save(UserSubscriptionInterface $userSubscription): void
    {
        assert($userSubscription instanceof PushSubscription);

        $this->repository->add($userSubscription);
        $this->flusher->flush();
    }

    public function delete(UserSubscriptionInterface $userSubscription): void
    {
        assert($userSubscription instanceof PushSubscription);

        $this->repository->remove($userSubscription);
        $this->flusher->flush();
    }
}
