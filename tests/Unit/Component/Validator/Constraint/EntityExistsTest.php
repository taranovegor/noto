<?php

namespace App\Tests\Unit\Component\Validator\Constraint;

use App\Component\Validator\Constraint\EntityExists;
use App\Component\Validator\Constraint\EntityExistsValidator;
use App\Entity\Project;
use PHPUnit\Framework\TestCase;

class EntityExistsTest extends TestCase
{
    public function testConstraintProperties(): void
    {
        $constraint = new EntityExists(
            entityClass: Project::class,
            field: 'id'
        );

        $this->assertEquals(Project::class, $constraint->entityClass);
        $this->assertEquals('id', $constraint->field);
        $this->assertEquals('The entity "{{ entity }}" does not exist.', $constraint->message);
    }

    public function testConstraintWithCustomMessage(): void
    {
        $customMessage = 'Project not found';
        $constraint = new EntityExists(
            entityClass: Project::class,
            field: 'prefix',
            message: $customMessage
        );

        $this->assertEquals($customMessage, $constraint->message);
    }

    public function testConstraintValidatedBy(): void
    {
        $constraint = new EntityExists(
            entityClass: Project::class,
            field: 'id'
        );

        $this->assertEquals(
            EntityExistsValidator::class,
            $constraint->validatedBy()
        );
    }

    public function testConstraintTargets(): void
    {
        $constraint = new EntityExists(
            entityClass: Project::class,
            field: 'id'
        );

        $this->assertEquals(
            \Symfony\Component\Validator\Constraint::PROPERTY_CONSTRAINT,
            $constraint->getTargets()
        );
    }
}
