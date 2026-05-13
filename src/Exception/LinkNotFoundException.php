<?php

namespace App\Exception;

use App\Entity\ReferenceableInterface;
use App\Enum\LinkKind;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class LinkNotFoundException extends \DomainException implements HttpExceptionInterface
{
    public function __construct(
        ReferenceableInterface $source,
        ReferenceableInterface $target,
        LinkKind $kind,
    ) {
        parent::__construct(\sprintf(
            'No %s link between %s(%s) and %s(%s).',
            $kind->value,
            substr(strrchr($source::class, '\\'), 1),
            $source->getRef()->id,
            substr(strrchr($target::class, '\\'), 1),
            $target->getRef()->id,
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
