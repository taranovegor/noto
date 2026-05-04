<?php

namespace App\Service\Stash;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use App\Component\Broadcaster\Normalizer\BroadcastNormalizerInterface;
use App\Entity\Stash;
use App\Factory\Stash\StashResponseDtoFactory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class StashBroadcastNormalizer implements BroadcastNormalizerInterface
{
    public function __construct(
        private StashResponseDtoFactory $factory,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function supports(BroadcastEvent $event, object $entity): bool
    {
        return $entity instanceof Stash;
    }

    public function normalize(object $entity): array
    {
        return (array) $this->normalizer->normalize($this->factory->create($entity));
    }
}
