<?php

namespace App\Component\Audio;

interface AudioProcessingHandler
{
    /**
     * @return iterable<\SplFileInfo>
     */
    public function handle(\SplFileInfo $file): iterable;
}
