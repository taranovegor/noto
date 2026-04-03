<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class EntityNotFoundException extends \DomainException implements HttpExceptionInterface
{
    private readonly string $criteria;

    /**
     * @param string|array<string, string> $criteria
     * @param array<string, string>        $headers
     */
    public function __construct(
        private readonly string $entityClass,
        string|array $criteria = [],
        string $message = 'Entity not found',
        int $code = 0,
        ?\Throwable $previous = null,
        /** @var array<string, string> $headers */
        private readonly array $headers = [],
    ) {
        $this->criteria = is_array($criteria)
            ? implode(', ', array_map(
                fn ($k, $v) => "$k=$v",
                array_keys($criteria),
                array_values($criteria),
            ))
            : "id=$criteria";

        parent::__construct(
            $message,
            $code,
            $previous
        );
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCriteria(): string
    {
        return $this->criteria;
    }
}
