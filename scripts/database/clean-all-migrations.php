<?php

/**
 * سكريبت شامل لتنظيف جدول migrations من جميع السجلات القديمة والمكررة
 * 
 * تعليمات الاستخدام:
 * 1. شغل هذا السكريبت قبل عمل git pull
 * 2. ثم اعمل git pull
 * 3. ثم شغل php artisan migrate
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧹 تنظيف سجلات migrations القديمة والمكررة...\n\n";

// قائمة جميع الملفات القديمة التي تم حذفها أو استبدالها
$oldMigrations = [
    // units migrations
    '2025_08_18_053433_create_units_table',
    '2025_08_21_020711_create_units_table',
    '2025_08_22_202801_update_units_table_structure',
    
    // property_types migrations
    '2025_08_18_045456_create_property_types_table',
    '2025_08_20_042106_create_property_types_table',
    '2025_08_21_create_property_types_table',
    
    // property_features migrations
    '2025_08_18_045547_create_property_features_table',
    '2025_08_21_create_property_features_table',
    
    // property_statuses migrations
    '2025_08_18_045522_create_property_statuses_table',
    '2025_08_21_create_property_statuses_table',
    
    // unit_contracts migrations
    '2025_08_18_063116_create_unit_contracts_table',
    '2025_08_22_222601_cleanup_unit_contracts_table',
];

$totalDeleted = 0;

echo "📋 السجلات المراد حذفها:\n";
echo str_repeat("-", 60) . "\n";

foreach ($oldMigrations as $migration) {
    $deleted = DB::table('migrations')
        ->where('migration', $migration)
        ->delete();
    
    if ($deleted) {
        echo "✅ تم حذف: $migration\n";
        $totalDeleted++;
    } else {
        echo "⏭️  غير موجود: $migration\n";
    }
}

echo str_repeat("-", 60) . "\n";
echo "📊 الملخص:\n";
echo "   • إجمالي السجلات المحذوفة: $totalDeleted\n";
echo "   • إجمالي السجلات المفحوصة: " . count($oldMigrations) . "\n";

if ($totalDeleted > 0) {
    echo "\n✨ تم التنظيف بنجاح!\n";
} else {
    echo "\n✅ لا توجد سجلات تحتاج للحذف - قاعدة البيانات نظيفة بالفعل!\n";
}

echo "\n📌 الخطوات التالية:\n";
echo "   1. git pull (لجلب التحديثات)\n";
echo "   2. php artisan migrate (لتشغيل الترحيلات الجديدة)\n\n";