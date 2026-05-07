<?php

namespace App\Dto\Attachment;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AttachmentsBatchDownloadDto
{
    /**
     * @param Uuid[] $ids
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1, max: 50)]
        #[Assert\All([new Assert\Uuid()])]
        public array $ids,
    ) {
    }
}
