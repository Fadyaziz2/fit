<?php

namespace App\Support;

class PermissionScope
{
    public const SCOPED_PERMISSIONS = [
        'user-list',
        'chat-center-list',
        'sms-center-list',
        'email-center-list',
    ];

    protected const ALIASES = [
        'chat-center-reply' => 'chat-center-list',
        'sms-center-send' => 'sms-center-list',
        'email-center-send' => 'email-center-list',
    ];

    public static function normalize(?string $permission): ?string
    {
        if (! $permission) {
            return null;
        }

        if (in_array($permission, self::SCOPED_PERMISSIONS, true)) {
            return $permission;
        }

        return self::ALIASES[$permission] ?? null;
    }

    public static function isScoped(?string $permission): bool
    {
        return self::normalize($permission) !== null;
    }
}
