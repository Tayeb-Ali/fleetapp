<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Default admin account (legacy ADMIN_USER): admin / admin123. */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrNew(['username' => 'admin']);

        $user->forceFill([
            'name' => 'Administrator',
            'email' => null,
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'active' => true,
        ])->save();
    }
}
