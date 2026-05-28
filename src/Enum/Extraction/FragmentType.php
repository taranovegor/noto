<?php

namespace App\Enum\Extraction;

enum FragmentType: string
{
    case AudioTranscript = 'audio_transcript';
    case Image = 'image';
    case Document = 'document';

    /**
     * Whether the fragment's content is produced later by an async worker
     * (transcription) rather than passed to the final model by reference.
     */
    public function isDeferred(): bool
    {
        return self::AudioTranscript === $this;
    }
}
