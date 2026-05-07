<?php

namespace App\EventSubscriber;

use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use App\Service\Link\LinkResolver;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(Events::onFlush, 10)]
final readonly class OwnershipCascadeSubscriber
{
    public function __construct(
        private LinkResolver $linkResolver,
        #[Autowire('@monolog.logger.ref')]
        private LoggerInterface $logger,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $processed = [];

        $i = 0;
        while (true) {
            $deletions = array_values($uow->getScheduledEntityDeletions());
            if ($i >= \count($deletions)) {
                break;
            }

            $entity = $deletions[$i];
            ++$i;

            if (!$entity instanceof ReferenceableInterface) {
                continue;
            }

            $id = $entity->getRef()->id->toRfc4122();
            if (isset($processed[$id])) {
                continue;
            }
            $processed[$id] = true;

            /* @phpstan-ignore argument.templateType */
            $children = $this->linkResolver->resolve($entity, kind: LinkKind::Ownership);
            foreach ($children as $child) {
                $this->logger->debug('Cascading deletion through Ownership link', [
                    'source' => $entity::getRefType()->value,
                    'sourceId' => $entity->getRef()->id->toRfc4122(),
                    'target' => $child::getRefType()->value,
                    'targetId' => $child->getRef()->id->toRfc4122(),
                ]);

                $em->remove($child);
                $classMetadata = $em->getClassMetadata($child::class);
                $uow->computeChangeSet($classMetadata, $child);
            }
        }
    }
}
