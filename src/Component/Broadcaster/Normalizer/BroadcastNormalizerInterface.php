<?php

namespace App\Component\Broadcaster\Normalizer;

use App\Component\Broadcaster\Enum\BroadcastEvent;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('broadcaster.broadcast_normalizer')]
interface BroadcastNormalizerInterface
{
    public function supports(BroadcastEvent $event, object $entity): bool;

    /**
     * @return array<string, mixed>
     */
    public function normalize(BroadcastEvent $event, object $entity): array;
}
