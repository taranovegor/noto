<?php

namespace App\Tests\Unit\Component\Validator\Constraint;

use App\Component\Validator\Constraint\EntityExists;
use App\Component\Validator\Constraint\EntityExistsValidator;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Context\ExecutionContext;

class EntityExistsValidatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityExistsValidator $validator;
    private ExecutionContext&MockObject $context;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = new EntityExistsValidator($this->entityManager);
        $this->context = $this->createMock(ExecutionContext::class);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithExistingEntity(): void
    {
        $id = Uuid::v7();
        $constraint = new EntityExists(entityClass: Project::class, field: 'id');
        $project = new Project('Test Project', 'TST');

        $repositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn($project);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Project::class)
            ->willReturn($repositoryMock);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($id, $constraint);
    }

    public function testValidateWithNonExistingEntity(): void
    {
        $id = Uuid::v7();
        $constraint = new EntityExists(entityClass: Project::class, field: 'id');

        $repositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Project::class)
            ->willReturn($repositoryMock);

        $violationBuilder = $this->createMock(\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ entity }}', 'Project')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($id, $constraint);
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new EntityExists(entityClass: Project::class, field: 'id');

        $this->entityManager->expects($this->never())
            ->method('getRepository');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    public function testValidateWithDifferentField(): void
    {
        $constraint = new EntityExists(entityClass: Project::class, field: 'prefix');
        $project = new Project('Test Project', 'TST');

        $repositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['prefix' => 'TST'])
            ->willReturn($project);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Project::class)
            ->willReturn($repositoryMock);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('TST', $constraint);
    }

    public function testValidateWithCustomMessage(): void
    {
        $customMessage = 'Custom error message';
        $constraint = new EntityExists(
            entityClass: Project::class,
            field: 'id',
            message: $customMessage
        );
        $id = Uuid::v7();

        $repositoryMock = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Project::class)
            ->willReturn($repositoryMock);

        $violationBuilder = $this->createMock(\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($customMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($id, $constraint);
    }
}
