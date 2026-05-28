<?php

namespace App\Component\Audio;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class AudioPreprocessor
{
    /**
     * @param iterable<AudioProcessingHandler> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.audio.processing_handler')]
        private iterable $handlers,
    ) {
    }

    /**
     * @return \SplFileInfo[]
     */
    public function process(\SplFileInfo $audioFile): array
    {
        $current = $audioFile;

        foreach ($this->handlers as $handler) {
            $chunks = [];

            foreach ($handler->handle($current) as $file) {
                $chunks[] = $file;
            }

            if (empty($chunks)) {
                continue;
            }

            if (1 === count($chunks) && $chunks[0]->getPathname() !== $current->getPathname()) {
                $current = $chunks[0];
                continue;
            }

            $this->cleanupIntermediate($current, $audioFile, $chunks);

            return $chunks;
        }

        throw new \RuntimeException('Audio processing chain exhausted without result.');
    }

    /**
     * @param array<\SplFileInfo> $result
     */
    private function cleanupIntermediate(\SplFileInfo $current, \SplFileInfo $original, array $result): void
    {
        $resultPaths = array_map(fn (\SplFileInfo $f) => $f->getPathname(), $result);

        if ($current->getPathname() !== $original->getPathname()
            && !in_array($current->getPathname(), $resultPaths, true)
            && file_exists($current->getPathname())) {
            @unlink($current->getPathname());
        }
    }
}
