<?php

namespace App\Tests\Unit\Validator;

use App\Entity\Attachment;
use App\Repository\LinkRepository;
use App\Validator\RefNotOwned;
use App\Validator\RefNotOwnedValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RefNotOwnedValidatorTest extends TestCase
{
    private LinkRepository&MockObject $linkRepository;
    private ExecutionContextInterface&MockObject $context;
    private RefNotOwnedValidator $validator;
    private RefNotOwned $constraint;

    protected function setUp(): void
    {
        $this->linkRepository = $this->createMock(LinkRepository::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new RefNotOwned();

        $this->validator = new RefNotOwnedValidator($this->linkRepository);
        $this->validator->initialize($this->context);
    }

    public function testNullValueSkipsValidation(): void
    {
        $this->linkRepository->expects($this->never())->method('hasOwnershipTarget');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate(null, $this->constraint);
    }

    public function testReferenceableWithNoOwnershipPassesValidation(): void
    {
        $attachment = new Attachment();

        $this->linkRepository
            ->expects($this->once())
            ->method('hasOwnershipTarget')
            ->with($attachment->ref->id)
            ->willReturn(false);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($attachment, $this->constraint);
    }

    public function testReferenceableAlreadyOwnedAddsViolation(): void
    {
        $attachment = new Attachment();

        $this->linkRepository
            ->expects($this->once())
            ->method('hasOwnershipTarget')
            ->willReturn(true);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);

        $this->validator->validate($attachment, $this->constraint);
    }

    public function testInvalidUuidStringSkipsValidation(): void
    {
        $this->linkRepository->expects($this->never())->method('hasOwnershipTarget');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('not-a-uuid', $this->constraint);
    }
}
