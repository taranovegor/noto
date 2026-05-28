<?php

namespace App\Service\Extraction;

use App\Component\Ai\Prompt\PromptProvider;
use App\Contract\HasExtractionInstructions;
use App\Entity\Extraction;
use App\Service\Ref\RefDereferencer;

final readonly class UserPromptBuilder
{
    public function __construct(
        private RefDereferencer $refDereferencer,
        private PromptProvider $promptProvider,
    ) {
    }

    public function build(Extraction $extraction): string
    {
        $parts = [];

        $defaultPrompt = $this->promptProvider->getUserPrompt($extraction->targetType->value);

        if (null !== $extraction->targetParent) {
            $parent = $this->refDereferencer->dereference($extraction->targetParent);
            if ($parent instanceof HasExtractionInstructions) {
                $instructions = $parent->getExtractionInstructions();
                if ($instructions) {
                    $parts[] = "## Parent context\n\n".$instructions;
                }
            }
        }

        $parts[] = "## Specific instructions\n\n".($extraction->prompt ?? $defaultPrompt);

        return implode("\n\n", $parts);
    }
}
