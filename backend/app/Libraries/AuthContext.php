<?php

namespace App\Libraries;

class AuthContext
{
    private static ?array $user = null;
    private static ?array $token = null;

    public static function set(array $user, array $token): void
    {
        self::$user = $user;
        self::$token = $token;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function token(): ?array
    {
        return self::$token;
    }

    public static function clear(): void
    {
        self::$user = null;
        self::$token = null;
    }
}

