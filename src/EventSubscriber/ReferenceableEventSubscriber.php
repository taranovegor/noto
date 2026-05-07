<?php

namespace App\EventSubscriber;

use App\Entity\ReferenceableInterface;
use App\Service\ReferenceableEventRegistry;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(Events::onFlush)]
final readonly class ReferenceableEventSubscriber
{
    public function __construct(
        private ReferenceableEventRegistry $eventRegistry,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        $this->dispatchFor($uow->getScheduledEntityInsertions(), 'created');
        $this->dispatchFor($uow->getScheduledEntityUpdates(), 'updated');
        $this->dispatchFor($uow->getScheduledEntityDeletions(), 'deleted');
    }

    /**
     * @param array<object> $entities
     */
    private function dispatchFor(array $entities, string $action): void
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof ReferenceableInterface) {
                continue;
            }

            $refType = $entity::getRefType();
            if ($this->eventRegistry->hasClass($refType)) {
                $class = $this->eventRegistry->getClass($refType);
                $this->eventDispatcher->dispatch(
                    /* @phpstan-ignore new.noConstructor */
                    new $class($entity),
                    sprintf('entity.%s.%s', $refType->value, $action),
                );
            }
        }
    }
}
