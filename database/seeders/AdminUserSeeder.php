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
            'email' => "admin@adaa.sd",
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'active' => true,
        ])->save();
     $user1 = User::firstOrNew(['username' => 'Elteyab']);

        $user1->forceFill([
            'name' => 'Administrator',
            'email' => "Elteyab@adaa.sd",
            'password' => Hash::make('123123'),
            'role' => 'admin',
            'active' => true,
        ])->save();
    
        
        }
}
