<?php

namespace App\Service\Extraction\Fragment;

use App\Dto\Extraction\FragmentResult;
use App\Enum\Extraction\FragmentType;
use App\Message\Extraction\MergeExtractionResults;
use App\Service\Extraction\ExtractionManager;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Records the outcome of a single deferred fragment and, once every fragment of
 * the extraction has reached a terminal state, triggers the merge. Centralises
 * the fan-in rule so every fragment producer shares one code path.
 */
final readonly class FragmentCompletion
{
    public function __construct(
        private ExtractionManager $extractionManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function complete(Uuid $extractionId, FragmentType $type, string $id, FragmentResult $result): void
    {
        $extraction = $this->extractionManager->get($extractionId);

        $allTerminal = $this->extractionManager->recordFragmentResult($extraction, $type, $id, $result);

        if ($allTerminal) {
            $this->messageBus->dispatch(new MergeExtractionResults($extractionId));
        }
    }
}
