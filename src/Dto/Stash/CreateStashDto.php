<?php

namespace App\Dto\Stash;

use App\Entity\Attachment;
use App\Enum\StashType;
use App\Validator\RefNotOwned;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateStashDto
{
    /**
     * @param Attachment[]|null $attachments
     */
    public function __construct(
        public StashType $type,
        #[Assert\Length(max: 65535)]
        public ?string $content = null,
        #[Assert\All([new RefNotOwned()])]
        public ?array $attachments = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (StashType::Text === $this->type && (null === $this->content || '' === $this->content)) {
            $context->buildViolation('Content is required for text stashes.')
                ->atPath('content')
                ->addViolation();
        }

        if (StashType::File === $this->type && 0 === count($this->attachments ?? [])) {
            $context->buildViolation('At least one file is required for file stashes.')
                ->atPath('attachments')
                ->addViolation();
        }
    }
}
