<?php

namespace App\Component\Ai\Extractor;

use App\Component\Ai\Extractor\Content\ContentEncoder;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Picks the right {@see ContentEncoder} for a content block and encodes it into
 * a Responses API input part.
 */
final readonly class ContentEncoderRegistry
{
    /**
     * @param iterable<ContentEncoder> $encoders
     */
    public function __construct(
        #[AutowireIterator('app.content_block_encoder')]
        private iterable $encoders,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function encode(object $block): array
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($block)) {
                return $encoder->encode($block);
            }
        }

        throw new \InvalidArgumentException(sprintf('Unsupported content block type: %s.', $block::class));
    }
}
