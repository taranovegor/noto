<?php

namespace App\Component\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EntityExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EntityExists) {
            throw new UnexpectedTypeException($constraint, EntityExists::class);
        }

        if (null === $value) {
            return;
        }

        /** @var class-string $entityClass */
        $entityClass = $constraint->entityClass;

        /** @var EntityRepository<object> $repository */
        $repository = $this->entityManager->getRepository($entityClass);
        $entity = $repository->findOneBy([$constraint->field => $value]);

        if (null === $entity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ entity }}', substr(strrchr($entityClass, '\\'), 1))
                ->addViolation();
        }
    }
}
