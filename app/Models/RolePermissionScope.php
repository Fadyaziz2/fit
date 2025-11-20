<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermissionScope extends Model
{
    use HasFactory;

    public const SCOPE_ALL = 'all';
    public const SCOPE_PRIVATE = 'private';

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
