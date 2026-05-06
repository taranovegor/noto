<?php

namespace App\Dto\Stash;

final readonly class UpdateStashDto
{
    public function __construct(
        public ?bool $pinned = null,
    ) {
    }
}
