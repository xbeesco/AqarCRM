<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Get email domain from APP_URL
     */
    private function getEmailDomain(): string
    {
        $appUrl = config('app.url', 'http://localhost');

        $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
        if (!str_contains($host, '.')) {
            $host .= '.local';
        }

        return $host;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domain = $this->getEmailDomain();

        User::updateOrCreate(
            ['email' => "superadmin@{$domain}"],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password'),
                'phone' => '0500000000',
                'type' => UserType::SUPER_ADMIN->value,
            ]
        );

        User::updateOrCreate(
            ['email' => "manager@{$domain}"],
            [
                'name' => 'المدير العام',
                'password' => Hash::make('password'),
                'phone' => '0500000001',
                'type' => UserType::ADMIN->value,
            ]
        );

        User::updateOrCreate(
            ['email' => "employee@{$domain}"],
            [
                'name' => 'موظف تجريبي',
                'password' => Hash::make('password'),
                'phone' => '0500000002',
                'type' => UserType::EMPLOYEE->value,
            ]
        );

        echo "Users seeded successfully! (Domain: {$domain})\n";
    }
}
