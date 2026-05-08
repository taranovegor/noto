<?php

namespace App\Component\Broadcaster\EventSubscriber;

use App\Component\Broadcaster\BroadcasterInterface;
use App\Component\Broadcaster\Config\BroadcastableConfig;
use App\Component\Broadcaster\Enum\BroadcastChannel;
use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::postUpdate)]
#[AsDoctrineListener(Events::preRemove)]
final readonly class BroadcastEvents
{
    public function __construct(
        private BroadcastNormalizer $normalizer,
        private BroadcasterInterface $broadcaster,
        private BroadcastableConfig $config,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->broadcast(BroadcastEvent::Created, $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        if ([] === $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($args->getObject())) {
            return;
        }

        $this->broadcast(BroadcastEvent::Updated, $args->getObject());
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->broadcast(BroadcastEvent::Deleted, $args->getObject());
    }

    private function broadcast(BroadcastEvent $event, object $entity): void
    {
        $namespace = $this->config->getNamespace($entity::class);
        if (null === $namespace) {
            return;
        }

        $normalized = $this->normalizer->normalize($event, $entity);
        $this->broadcaster->broadcast($namespace, BroadcastChannel::Events->value, $normalized, $event);
    }
}
