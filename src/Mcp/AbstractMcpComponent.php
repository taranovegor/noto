<?php

namespace App\Mcp;

use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractMcpComponent implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public static function getSubscribedServices(): array
    {
        return [
            'serializer' => NormalizerInterface::class,
            'validator' => ValidatorInterface::class,
        ];
    }

    #[Required]
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }

    /**
     * Validate an object and return normalized violation data.
     *
     * @param array<string> $groups
     */
    protected function validate(object $object, ?array $groups = null): ConstraintViolationListInterface
    {
        /** @var ValidatorInterface $validator */
        static $validator = $this->container->get('validator');

        return $validator->validate($object, groups: $groups);
    }

    /**
     * @param object               $object
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function normalize(mixed $object, array $context = []): array
    {
        /** @var NormalizerInterface $serializer */
        static $serializer = $this->container->get('serializer');

        return $serializer->normalize($object, context: $context);
    }

    /**
     * Denormalizes request arguments into an object of the specified class.
     *
     * @template T of object
     *
     * @param class-string<T>      $class          The fully qualified class name for denormalization
     * @param RequestContext       $requestContext The request context containing data to process
     * @param array<string, mixed> $context
     *
     * @return T
     */
    protected function denormalize(string $class, RequestContext $requestContext, array $context = []): mixed
    {
        /** @var DenormalizerInterface $denormalizer */
        static $denormalizer = $this->container->get('serializer');

        $request = $requestContext->getRequest();
        if (!$request instanceof CallToolRequest) {
            throw new \InvalidArgumentException(sprintf('Expected %s, but received %s instead', CallToolRequest::class, $request::class));
        }

        return $denormalizer->denormalize($request->arguments, $class, context: $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function yaml(mixed $content, array $context = []): string
    {
        /** @var SerializerInterface $serializer */
        static $serializer = $this->container->get('serializer');

        return $serializer->serialize($content, 'yaml', $context);
    }
}
