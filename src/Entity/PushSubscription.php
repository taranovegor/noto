<?php

namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_subscriptions')]
class PushSubscription implements UserSubscriptionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) User $user;

    #[ORM\Column(type: 'string')]
    public private(set) string $subscriptionHash;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    public private(set) array $subscription;

    /**
     * @param array<string, mixed> $subscription
     */
    public function __construct(User $user, string $subscriptionHash, array $subscription)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->subscriptionHash = $subscriptionHash;
        $this->subscription = $subscription;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubscriptionHash(): string
    {
        return $this->subscriptionHash;
    }

    public function getEndpoint(): string
    {
        return $this->subscription['endpoint'];
    }

    public function getPublicKey(): string
    {
        return $this->subscription['keys']['p256dh'];
    }

    public function getAuthToken(): string
    {
        return $this->subscription['keys']['auth'];
    }

    public function getContentEncoding(): string
    {
        return $this->subscription['content-encoding'] ?? 'aesgcm';
    }
}
