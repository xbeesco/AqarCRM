<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "🔧 إضافة عمود remember_token...\n\n";

// Check if column exists
if (Schema::hasColumn('users', 'remember_token')) {
    echo "✅ العمود موجود بالفعل!\n";
} else {
    echo "📝 إضافة العمود...\n";
    
    // Add the column using raw SQL
    DB::statement('ALTER TABLE users ADD COLUMN remember_token VARCHAR(100) NULL AFTER password');
    
    echo "✅ تم إضافة عمود remember_token بنجاح!\n";
}

echo "\n📊 فحص المستخدمين الموجودين:\n";
$users = DB::table('users')->select('id', 'email', 'type')->get();

foreach ($users as $user) {
    echo "   - {$user->email} (type: {$user->type})\n";
}

echo "\n✅ اكتمل الإعداد\n";