<?php

namespace App\EventSubscriber;

use App\Contract\HasUpdatedAtInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

final readonly class UpdatedAtSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::preUpdate, Events::prePersist];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->touch($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->touch($args->getObject());
    }

    private function touch(object $entity): void
    {
        if ($entity instanceof HasUpdatedAtInterface) {
            $entity->touchUpdatedAt();
        }
    }
}
