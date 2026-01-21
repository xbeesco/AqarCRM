<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Helpers\AppHelper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => User::generateEmail('superadmin')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '0500000000',
                'type' => UserType::SUPER_ADMIN->value,
            ]
        );

        $domain = AppHelper::getEmailDomain();
        echo "Users seeded successfully! (Domain: {$domain})\n";
    }
}
