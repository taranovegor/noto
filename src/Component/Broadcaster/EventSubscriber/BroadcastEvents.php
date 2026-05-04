<?php

namespace App\Component\Broadcaster\EventSubscriber;

use App\Component\Broadcaster\Attribute\Broadcastable;
use App\Component\Broadcaster\BroadcasterInterface;
use App\Component\Broadcaster\Enum\BroadcastChannel;
use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::postUpdate)]
final readonly class BroadcastEvents
{
    public function __construct(
        private BroadcastNormalizer $normalizer,
        private BroadcasterInterface $broadcaster,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->broadcast(BroadcastEvent::Created, $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->broadcast(BroadcastEvent::Updated, $args->getObject());
    }

    private function broadcast(BroadcastEvent $event, object $entity): void
    {
        $attribute = $this->getBroadcastableAttribute($entity);
        if (!$attribute instanceof Broadcastable) {
            return;
        }

        $normalized = $this->normalizer->normalize($event, $entity);
        $this->broadcaster->broadcast($attribute->namespace, BroadcastChannel::Events->value, $normalized);
    }

    private function getBroadcastableAttribute(object $entity): ?object
    {
        $reflection = new \ReflectionClass($entity);
        $attributes = $reflection->getAttributes(Broadcastable::class);

        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }
}
