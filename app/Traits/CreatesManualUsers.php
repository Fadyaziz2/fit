<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CreatesManualUsers
{
    protected function createManualUser(?string $name, ?string $phone): User
    {
        $safeName = $name ?: __('message.manual_free_guest');
        $username = Str::slug(mb_substr($safeName, 0, 20), '.') ?: 'manual-user';
        $username .= '.' . Str::lower(Str::random(6));

        $email = $username . '@manual.local';

        $user = User::create([
            'username' => $username,
            'first_name' => $safeName,
            'last_name' => null,
            'display_name' => $safeName,
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
            'user_type' => 'user',
            'status' => 'pending',
            'phone_number' => $phone,
        ]);

        $user->assignRole('user');

        return $user;
    }
}
