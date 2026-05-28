<?php

namespace App\Component\Ai\Extractor\Content;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.content_block_encoder', ['priority' => 20])]
final readonly class FileEncoder implements ContentEncoder
{
    public function supports(object $block): bool
    {
        return $block instanceof File;
    }

    public function encode(object $block): array
    {
        \assert($block instanceof File);

        return [
            'type' => 'input_file',
            'file_url' => $block->url,
            'filename' => $block->filename,
        ];
    }
}
