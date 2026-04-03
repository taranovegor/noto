<?php

namespace App\Component\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate that an entity exists in the database.
 *
 * Usage:
 * #[EntityExists(entityClass: Project::class, field: 'id')]
 * public ?Uuid $projectId
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class EntityExists extends Constraint
{
    public function __construct(
        public readonly string $entityClass,
        public readonly string $field,
        public readonly string $message = 'The entity "{{ entity }}" does not exist.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function validatedBy(): string
    {
        return EntityExistsValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
