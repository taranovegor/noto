<?php

namespace App\Factory\Extraction;

use App\Dto\Extraction\ExtractionResponseDto;
use App\Entity\Attachment;
use App\Entity\Extraction;
use App\Enum\LinkKind;
use App\Factory\Attachment\AttachmentResponseDtoFactory;
use App\Service\Link\LinkResolver;

readonly class ExtractionResponseDtoFactory
{
    public function __construct(
        private LinkResolver $linkResolver,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
    ) {
    }

    public function create(Extraction $extraction): ExtractionResponseDto
    {
        $sources = array_map(
            $this->attachmentResponseDtoFactory->create(...),
            $this->linkResolver->resolve($extraction->getRef(), LinkKind::Reference, Attachment::class),
        ) ?: null;

        return new ExtractionResponseDto(
            $extraction->id,
            $extraction->status,
            $extraction->targetType,
            $extraction->errorMessage,
            $extraction->createdAt,
            $extraction->updatedAt,
            $extraction->targetParent?->id,
            $extraction->prompt,
            $sources,
        );
    }
}
