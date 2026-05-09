<?php

namespace App\EventSubscriber;

use App\Entity\ReferenceableInterface;
use App\Service\ReferenceableEventRegistry;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

#[AsDoctrineListener(Events::onFlush)]
#[AsDoctrineListener(Events::postFlush)]
final class ReferenceableEventSubscriber implements ResetInterface
{
    /** @var array<array{object, string}> */
    private array $pending = [];

    public function __construct(
        private readonly ReferenceableEventRegistry $eventRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->pending = [];

        $uow = $args->getObjectManager()->getUnitOfWork();

        $this->collect($uow->getScheduledEntityInsertions(), 'created');
        $this->collect($uow->getScheduledEntityUpdates(), 'updated');
        $this->collect($uow->getScheduledEntityDeletions(), 'deleted');
    }

    public function postFlush(): void
    {
        $events = $this->pending;
        $this->pending = [];

        foreach ($events as [$event, $name]) {
            $this->eventDispatcher->dispatch($event, $name);
        }
    }

    public function reset(): void
    {
        $this->pending = [];
    }

    /**
     * @param array<object> $entities
     */
    private function collect(array $entities, string $action): void
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof ReferenceableInterface) {
                continue;
            }

            $refType = $entity::getRefType();
            if ($this->eventRegistry->hasClass($refType)) {
                $class = $this->eventRegistry->getClass($refType);
                $this->pending[] = [
                    /* @phpstan-ignore new.noConstructor */
                    new $class($entity),
                    sprintf('entity.%s.%s', $refType->value, $action),
                ];
            }
        }
    }
}
