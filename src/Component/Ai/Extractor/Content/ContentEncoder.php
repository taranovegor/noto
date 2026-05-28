<?php

namespace App\Component\Ai\Extractor\Content;

interface ContentEncoder
{
    public function supports(object $block): bool;

    /**
     * Encodes a content block into a Responses API input part (input_text,
     * input_image, input_file).
     *
     * @return array<string, mixed>
     */
    public function encode(object $block): array;
}
