<?php

namespace App\Dto\Extraction;

use App\Dto\Attachment\AttachmentResponseDto;
use App\Enum\ExtractionStatus;
use App\Enum\RefType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

readonly class ExtractionResponseDto
{
    /**
     * @param AttachmentResponseDto[]|null $sources
     */
    public function __construct(
        #[Groups(['extraction:read', 'extraction:list'])]
        public Uuid $id,
        #[Groups(['extraction:read', 'extraction:list'])]
        public ExtractionStatus $status,
        #[Groups(['extraction:read', 'extraction:list'])]
        public RefType $targetType,
        #[Groups(['extraction:read', 'extraction:list'])]
        public ?string $errorMessage,
        #[Groups(['extraction:read', 'extraction:list'])]
        public \DateTimeInterface $createdAt,
        #[Groups(['extraction:read', 'extraction:list'])]
        public \DateTimeInterface $updatedAt,
        #[Groups(['extraction:read'])]
        public ?Uuid $targetParentId,
        #[Groups(['extraction:read'])]
        public ?string $prompt,
        #[Groups(['extraction:read'])]
        public ?array $sources = null,
    ) {
    }
}
