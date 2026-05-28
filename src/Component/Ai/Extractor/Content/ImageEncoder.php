<?php

namespace App\Component\Ai\Extractor\Content;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.content_block_encoder', ['priority' => 30])]
final readonly class ImageEncoder implements ContentEncoder
{
    public function supports(object $block): bool
    {
        return $block instanceof Image;
    }

    public function encode(object $block): array
    {
        \assert($block instanceof Image);

        return [
            'type' => 'input_image',
            'image_url' => $block->url,
        ];
    }
}
