<?php

namespace App\Enum;

enum Role: string
{
    public const string ROLE_USER = 'ROLE_USER';

    case User = self::ROLE_USER;
}
