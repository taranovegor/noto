<?php

namespace App\Dto\Extraction;

final readonly class FragmentResult
{
    private function __construct(
        public ?string $result,
        public ?string $error,
    ) {
    }

    public static function success(string $result): self
    {
        return new self($result, null);
    }

    public static function failure(string $error): self
    {
        return new self(null, $error);
    }

    public function isSuccess(): bool
    {
        return null === $this->error;
    }
}
