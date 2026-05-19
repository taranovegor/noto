<?php

namespace App\Exception;

use App\Entity\Ref;
use App\Enum\LinkKind;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class LinkNotFoundException extends \DomainException implements HttpExceptionInterface
{
    public function __construct(Ref $source, Ref $target, LinkKind $kind)
    {
        parent::__construct(\sprintf(
            'No %s link between ref %s and ref %s.',
            $kind->value,
            $source->id,
            $target->id,
        ));
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
        return [];
    }
}
