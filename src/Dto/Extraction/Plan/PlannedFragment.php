<?php

namespace App\Dto\Extraction\Plan;

use App\Enum\Extraction\FragmentType;

/**
 * One unit of work an {@see \App\Service\Extraction\Processor\AttachmentProcessor} plans
 * for an attachment. Either a workable fragment (carrying the {@see SourceRef} of
 * its bytes) or a failure planned upfront — e.g. an unsupported file type — which
 * the orchestrator records as a failed fragment without aborting the rest.
 */
final readonly class PlannedFragment
{
    private function __construct(
        public FragmentType $type,
        public ?SourceRef $source,
        public ?string $error,
    ) {
    }

    public static function of(FragmentType $type, SourceRef $source): self
    {
        return new self($type, $source, null);
    }

    public static function failed(FragmentType $type, string $error): self
    {
        return new self($type, null, $error);
    }

    public function isFailed(): bool
    {
        return null !== $this->error;
    }
}
