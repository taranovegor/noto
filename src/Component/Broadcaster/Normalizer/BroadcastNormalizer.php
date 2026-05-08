<?php

namespace App\Component\Broadcaster\Normalizer;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class BroadcastNormalizer
{
    /**
     * @param iterable<BroadcastNormalizerInterface> $normalizers
     */
    public function __construct(
        #[AutowireIterator('broadcaster.broadcast_normalizer')]
        private iterable $normalizers,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(BroadcastEvent $event, object $entity): array
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($event, $entity)) {
                return $normalizer->normalize($event, $entity);
            }
        }

        throw new \RuntimeException(sprintf('No broadcast normalizer found for "%s" with event "%s".', $entity::class, $event->name));
    }
}
