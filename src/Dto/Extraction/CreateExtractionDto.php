<?php

namespace App\Dto\Extraction;

use App\Entity\Attachment;
use App\Entity\Ref;
use App\Enum\RefType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateExtractionDto
{
    /**
     * @param Attachment[] $attachments
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        public array $attachments,
        public RefType $targetType,
        public ?Ref $targetParent = null,
        public ?string $prompt = null,
    ) {
    }

    #[Assert\Callback]
    public function validateTargetParent(ExecutionContextInterface $context): void
    {
        $expected = $this->targetType->getParentType();

        if (null !== $expected) {
            if (null === $this->targetParent) {
                $context->buildViolation('targetParent is required when targetType is "{{ targetType}}".')
                    ->setParameter('{{ targetType }}', $this->targetType->value)
                    ->addViolation();

                return;
            }

            if ($this->targetParent->type !== $expected) {
                $context->buildViolation('targetParent must be of type "{{ expected }}" but got "{{ actual }}".')
                    ->setParameter('{{ expected }}', $expected->value)
                    ->setParameter('{{ actual }}', $this->targetParent->type->value)
                    ->addViolation();
            }

            return;
        }

        if (null !== $this->targetParent) {
            $context->buildViolation('targetParent must be null for targetType "{{ targetType }}".')
                ->setParameter('{{ targetType }}', $this->targetType->value)
                ->addViolation();
        }
    }
}
