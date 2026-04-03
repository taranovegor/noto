<?php

namespace App\Contract;

interface HasUpdatedAtInterface
{
    public function getUpdatedAt(): \DateTimeImmutable;

    public function touchUpdatedAt(): void;
}
