<?php

namespace App\Component\Ai\Extractor\Content;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.content_block_encoder', ['priority' => 10])]
final readonly class TextEncoder implements ContentEncoder
{
    public function supports(object $block): bool
    {
        return $block instanceof Text;
    }

    public function encode(object $block): array
    {
        \assert($block instanceof Text);

        return ['type' => 'input_text', 'text' => $block->text];
    }
}
