<?php

namespace App\Service;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use App\Entity\ReferenceableInterface;

final readonly class DeleteBroadcastNormalizer implements BroadcastNormalizerInterface
{
    public function supports(BroadcastEvent $event, object $entity): bool
    {
        return BroadcastEvent::Deleted === $event && $entity instanceof ReferenceableInterface;
    }

    /**
     * @param object&ReferenceableInterface $entity
     */
    public function normalize(BroadcastEvent $event, object $entity): array
    {
        return ['id' => $entity->getRef()->id];
    }
}
