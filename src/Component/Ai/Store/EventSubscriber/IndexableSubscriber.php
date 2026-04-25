<?php

namespace App\Component\Ai\Store\EventSubscriber;

use App\Component\Ai\Store\Config\IndexableConfig;
use App\Component\Ai\Store\Document\IndexableReference;
use App\Component\Ai\Store\Message\IndexObject;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ResetInterface;

#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::preUpdate)]
#[AsDoctrineListener(Events::postUpdate)]
final readonly class IndexableSubscriber implements ResetInterface
{
    /**
     * @var \SplObjectStorage<object, true>
     */
    private \SplObjectStorage $updateQueue;

    public function __construct(
        private IndexableConfig $indexableConfig,
        private MessageBusInterface $messageBus,
        #[Autowire('@monolog.logger.ai_index')]
        private LoggerInterface $logger,
    ) {
        $this->updateQueue = new \SplObjectStorage();
    }

    public function reset(): void
    {
        $this->updateQueue->removeAll($this->updateQueue);
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();
        if (!$this->isIndexable($object)) {
            return;
        }

        $this->handle($object);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $object = $args->getObject();
        if (!$this->isIndexable($object)) {
            return;
        }

        $changedFields = array_keys($args->getEntityChangeSet());
        $indexableFields = $this->indexableConfig->fields($object::class);

        if (empty(array_intersect($indexableFields, $changedFields))) {
            return;
        }

        $this->updateQueue->attach($object);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $object = $args->getObject();
        if (!$this->updateQueue->contains($object)) {
            return;
        }

        $this->updateQueue->detach($object);
        $this->handle($object);
    }

    private function isIndexable(object $entity): bool
    {
        return in_array($entity::class, $this->indexableConfig->classes(), true);
    }

    private function handle(object $object): void
    {
        $this->logger->debug('Dispatching index.', ['class' => $object::class]);

        $identifierFieldName = $this->indexableConfig->identifierField($object::class);
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($identifierFieldName);
        $objectId = (string) $property->getValue($object);

        $this->messageBus->dispatch(new IndexObject(new IndexableReference($object::class, $objectId)));
    }
}
