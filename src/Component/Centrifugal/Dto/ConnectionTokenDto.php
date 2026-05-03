<?php

namespace App\Component\Centrifugal\Dto;

readonly class ConnectionTokenDto
{
    public function __construct(
        public string $userId,
        public string $token,
    ) {
    }
}
