<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

echo "🔑 إنشاء مستخدم مدير...\n\n";

$admin = User::updateOrCreate(
    ['email' => 'admin@aqarcrm.test'],
    [
        'name' => 'مدير النظام',
        'password' => Hash::make('password'),
        'phone' => '0500000000',
        'type' => UserType::SUPER_ADMIN->value, // استخدام النظام الجديد
    ]
);

echo "✅ تم إنشاء مستخدم المدير:\n";
echo "   البريد الإلكتروني: admin@aqarcrm.test\n";
echo "   كلمة المرور: password\n";
echo "   النوع: " . $admin->type . "\n";