<?php

namespace App\Validator;

use App\Entity\ReferenceableInterface;
use App\Repository\LinkRepository;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class RefNotOwnedValidator extends ConstraintValidator
{
    public function __construct(
        private readonly LinkRepository $linkRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RefNotOwned) {
            throw new UnexpectedTypeException($constraint, RefNotOwned::class);
        }

        if (null === $value) {
            return;
        }

        $uuid = match (true) {
            $value instanceof ReferenceableInterface => $value->getRef()->id,
            $value instanceof Uuid => $value,
            default => null,
        };

        if (null === $uuid) {
            try {
                $uuid = Uuid::fromString((string) $value);
            } catch (\InvalidArgumentException) {
                return;
            }
        }

        if ($this->linkRepository->hasOwnershipTarget($uuid)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
