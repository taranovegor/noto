<?php

namespace App\Repository;

use App\Entity\PushSubscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PushSubscription>
 */
class PushSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PushSubscription::class);
    }

    public function add(PushSubscription $pushSubscription): void
    {
        $this->getEntityManager()->persist($pushSubscription);
    }

    public function remove(PushSubscription $userSubscription): void
    {
        $this->getEntityManager()->remove($userSubscription);
    }

    /**
     * @return array<PushSubscription>
     */
    public function findByUser(User $user): array
    {
        return $this->findBy([
            'user' => $user,
        ]);
    }

    public function findOneByUserAndHash(User $user, string $subscriptionHash): ?PushSubscription
    {
        return $this->findOneBy([
            'user' => $user,
            'subscriptionHash' => $subscriptionHash,
        ]);
    }

    /**
     * @return array<PushSubscription>
     */
    public function findByHash(string $subscriptionHash): array
    {
        return $this->findBy([
            'subscriptionHash' => $subscriptionHash,
        ]);
    }
}
