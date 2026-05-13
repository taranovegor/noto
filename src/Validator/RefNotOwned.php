<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that the given UUID has no existing Ownership link as a target.
 *
 * Applies to any Ref-backed entity (Attachment, Note, Task, etc.).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class RefNotOwned extends Constraint
{
    public string $message = 'This entity is already owned by another entity.';

    public function validatedBy(): string
    {
        return RefNotOwnedValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
