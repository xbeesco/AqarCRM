<?php

/**
 * سكريبت تنظيف جدول migrations من سجلات units القديمة
 * 
 * تعليمات الاستخدام للفريق:
 * 1. شغل هذا السكريبت قبل عمل git pull
 * 2. ثم اعمل git pull
 * 3. ثم شغل php artisan migrate
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧹 تنظيف سجلات migrations القديمة لجدول units...\n\n";

// قائمة الملفات القديمة التي تم حذفها
$oldMigrations = [
    '2025_08_18_053433_create_units_table',
    '2025_08_21_020711_create_units_table', 
    '2025_08_22_202801_update_units_table_structure'
];

foreach ($oldMigrations as $migration) {
    $deleted = DB::table('migrations')
        ->where('migration', $migration)
        ->delete();
    
    if ($deleted) {
        echo "✅ تم حذف: $migration\n";
    } else {
        echo "⏭️  غير موجود: $migration\n";
    }
}

echo "\n✨ تم التنظيف بنجاح!\n";
echo "الآن يمكنك تشغيل: php artisan migrate\n";