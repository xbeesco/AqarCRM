<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;

echo "🔧 إصلاح جميع المستخدمين المدراء...\n\n";

// Fix admin@aqarcrm.com
$admin1 = User::where('email', 'admin@aqarcrm.com')->first();
if ($admin1) {
    $admin1->user_type = UserType::SUPER_ADMIN->value;
    $admin1->password = Hash::make('password');
    $admin1->save();
    echo "✅ تم تحديث admin@aqarcrm.com\n";
    echo "   Type: " . $admin1->user_type . "\n";
    echo "   Password: password\n\n";
}

// Fix admin@aqarcrm.test
$admin2 = User::where('email', 'admin@aqarcrm.test')->first();
if ($admin2) {
    $admin2->user_type = UserType::SUPER_ADMIN->value;
    $admin2->password = Hash::make('password');
    $admin2->save();
    echo "✅ تم تحديث admin@aqarcrm.test\n";
    echo "   Type: " . $admin2->user_type . "\n";
    echo "   Password: password\n\n";
}

// Fix admin@example.com
$admin3 = User::where('email', 'admin@example.com')->first();
if ($admin3) {
    $admin3->user_type = UserType::ADMIN->value;
    $admin3->password = Hash::make('password');
    $admin3->save();
    echo "✅ تم تحديث admin@example.com\n";
    echo "   Type: " . $admin3->user_type . "\n";
    echo "   Password: password\n\n";
}

// Fix other users
$employee = User::where('email', 'employee@example.com')->first();
if ($employee && !$employee->user_type) {
    $employee->user_type = UserType::EMPLOYEE->value;
    $employee->password = Hash::make('password');
    $employee->save();
    echo "✅ تم تحديث employee@example.com (type: employee)\n";
}

$owner = User::where('email', 'owner@example.com')->first();
if ($owner && !$owner->user_type) {
    $owner->user_type = UserType::OWNER->value;
    $owner->password = Hash::make('password');
    $owner->save();
    echo "✅ تم تحديث owner@example.com (type: owner)\n";
}

$tenant = User::where('email', 'tenant@example.com')->first();
if ($tenant && !$tenant->user_type) {
    $tenant->user_type = UserType::TENANT->value;
    $tenant->password = Hash::make('password');
    $tenant->save();
    echo "✅ تم تحديث tenant@example.com (type: tenant)\n";
}

echo "\n📋 المستخدمون المتاحون للدخول:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📧 admin@aqarcrm.com    / password  (super_admin)\n";
echo "📧 admin@aqarcrm.test   / password  (super_admin)\n";
echo "📧 admin@example.com    / password  (admin)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "\n✅ اكتمل الإصلاح!\n";