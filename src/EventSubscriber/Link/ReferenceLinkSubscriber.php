<?php

namespace App\EventSubscriber\Link;

use App\Contract\LinkSourceInterface;
use App\Entity\Link;
use App\Entity\ReferenceableInterface;
use App\Service\Link\ReferenceLinkSynchronizer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Mapping\ClassMetadata;
use League\CommonMark\Exception\CommonMarkException;

#[AsDoctrineListener(Events::onFlush)]
final readonly class ReferenceLinkSubscriber
{
    public function __construct(
        private ReferenceLinkSynchronizer $synchronizer,
    ) {
    }

    /**
     * @throws CommonMarkException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        /** @var ClassMetadata<Link> $linkMetadata */
        $linkMetadata = $em->getClassMetadata(Link::class);

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
        );
        foreach ($entities as $entity) {
            if ($entity instanceof LinkSourceInterface && $entity instanceof ReferenceableInterface) {
                foreach ($this->synchronizer->sync($entity) as $link) {
                    $uow->computeChangeSet($linkMetadata, $link);
                }
            }
        }
    }
}
