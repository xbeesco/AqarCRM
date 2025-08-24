<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Enums\UserType;

echo "🔧 إصلاح مستخدم Admin...\n\n";

$user = User::where('email', 'admin@aqarcrm.test')->first();

if ($user) {
    $user->type = UserType::SUPER_ADMIN->value;
    $user->save();
    
    echo "✅ تم تحديث المستخدم:\n";
    echo "   Email: " . $user->email . "\n";
    echo "   Type: " . $user->type . "\n";
    
    // التحقق من canAccessPanel
    $canAccess = $user->canAccessPanel(new \Filament\Panel('admin'));
    echo "   Can Access Panel: " . ($canAccess ? "Yes ✅" : "No ❌") . "\n";
} else {
    echo "❌ المستخدم غير موجود!\n";
    echo "جاري إنشاء مستخدم جديد...\n\n";
    
    $user = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin@aqarcrm.test',
        'password' => bcrypt('password'),
        'phone' => '0500000000',
        'type' => UserType::SUPER_ADMIN->value,
    ]);
    
    echo "✅ تم إنشاء المستخدم:\n";
    echo "   Email: " . $user->email . "\n";
    echo "   Password: password\n";
    echo "   Type: " . $user->type . "\n";
}