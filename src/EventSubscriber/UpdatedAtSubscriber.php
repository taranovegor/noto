<?php

namespace App\EventSubscriber;

use App\Contract\HasUpdatedAtInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(Events::prePersist)]
#[AsDoctrineListener(Events::preUpdate)]
final readonly class UpdatedAtSubscriber
{
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
