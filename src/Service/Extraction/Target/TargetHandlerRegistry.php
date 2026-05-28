<?php

namespace App\Service\Extraction\Target;

use App\Enum\RefType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class TargetHandlerRegistry
{
    /**
     * @param iterable<ExtractionTargetHandler> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.extraction.target_handler')]
        private iterable $handlers,
    ) {
    }

    public function get(RefType $type): ExtractionTargetHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return $handler;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unsupported target type: %s', $type->value));
    }

    /**
     * @return class-string
     */
    public function getSchemaClass(RefType $type): string
    {
        return $this->get($type)->getOutputSchema();
    }
}
