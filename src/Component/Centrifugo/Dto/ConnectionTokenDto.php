<?php

namespace App\Component\Centrifugo\Dto;

readonly class ConnectionTokenDto
{
    public function __construct(
        public string $userId,
        public string $token,
    ) {
    }
}
