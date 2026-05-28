<?php

namespace App\Service\Extraction\Processor;

use App\Dto\Extraction\Plan\PlannedFragment;
use App\Entity\Attachment;

interface AttachmentProcessor
{
    public function supports(Attachment $attachment): bool;

    /**
     * Plans the extraction fragments for a single attachment, without assigning
     * indexes or dispatching anything — the orchestrator wires those up.
     *
     * @return iterable<PlannedFragment>
     */
    public function plan(Attachment $attachment): iterable;
}
