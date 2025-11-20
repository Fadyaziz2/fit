<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermissionScope extends Model
{
    use HasFactory;

    public const SCOPE_ALL = 'all';
    public const SCOPE_PRIVATE = 'private';

    public static function normalizeScope(string $scope): string
    {
        $normalized = strtolower(trim($scope));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'all', 'all_user', 'all_users' => self::SCOPE_ALL,
            'private', 'private_user', 'private_users' => self::SCOPE_PRIVATE,
            default => in_array($scope, array_keys(self::options()), true)
                ? $scope
                : self::SCOPE_ALL,
        };
    }

    protected $fillable = [
        'role_id',
        'permission_name',
        'scope',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public static function options(): array
    {
        return [
            self::SCOPE_ALL => self::SCOPE_ALL,
            self::SCOPE_PRIVATE => self::SCOPE_PRIVATE,
        ];
    }
}
