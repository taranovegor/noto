<?php

namespace App\Serializer;

use App\Exception\EntityNotFoundException;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class EntityNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && !$this->entityManager->getMetadataFactory()->isTransient($data::class);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): int|string|array
    {
        $ids = $this->entityManager->getClassMetadata($data::class)->getIdentifierValues($data);

        return 1 === count($ids) ? current($ids) : $ids;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_scalar($data)
            && class_exists($type)
            && !$this->entityManager->getMetadataFactory()->isTransient($type);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): object
    {
        try {
            /** @var class-string<object> $type */
            $entity = $this->entityManager->getRepository($type)->find($data);
            if (null === $entity) {
                throw new EntityNotFoundException($type, $data);
            }

            return $entity;
        } catch (EntityNotFoundException|ValueNotConvertible $e) {
            throw new NotNormalizableValueException(message: sprintf('Entity "%s" with id "%s" not found.', $type, $data), previous: $e, currentType: substr(strrchr($type, '\\'), 1), expectedTypes: [$type], path: $context['deserialization_path'] ?? null, useMessageForUser: true);
        }
    }
}
