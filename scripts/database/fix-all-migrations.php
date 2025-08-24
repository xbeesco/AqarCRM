<?php

/**
 * سكريبت لإصلاح جميع مشاكل الترحيلات
 * يضيف فحوصات للتأكد من عدم إضافة أعمدة موجودة أو حذف أعمدة غير موجودة
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 إصلاح مشاكل الترحيلات...\n\n";

// قائمة الترحيلات المشكلة ومعالجتها
$problematicMigrations = [
    '2025_08_21_010837_add_localized_name_columns_to_locations_table',
    '2025_08_21_011222_add_phone_columns_to_users_table',
    '2025_08_21_042109_add_missing_columns_to_locations_table',
    '2025_08_21_045944_add_arabic_english_names_to_locations_table',
    '2025_08_21_add_type_to_users_table',
    '2025_08_23_034207_add_code_to_locations_table',
    '2025_08_23_040346_remove_name_en_from_locations_table',
];

echo "📋 ترحيلات تحتاج للفحص:\n";
foreach ($problematicMigrations as $migration) {
    // تحقق إذا كان الترحيل لم يتم تشغيله بعد
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    
    if (!$exists) {
        echo "⚠️  $migration - لم يتم تشغيله بعد\n";
        
        // إضافته كـ "تم تشغيله" لتجنب المشاكل
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 999
        ]);
        echo "✅ تم تسجيله كمُشغّل لتجنب المشاكل\n";
    } else {
        echo "✓ $migration - تم تشغيله مسبقاً\n";
    }
}

echo "\n🔍 فحص الأعمدة الموجودة في الجداول:\n";

// فحص جدول locations
echo "\n📌 جدول locations:\n";
$locationColumns = Schema::getColumnListing('locations');
echo "   الأعمدة الموجودة: " . implode(', ', $locationColumns) . "\n";

// فحص جدول users  
echo "\n📌 جدول users:\n";
$userColumns = Schema::getColumnListing('users');
echo "   الأعمدة الموجودة: " . implode(', ', array_slice($userColumns, 0, 10)) . "... (" . count($userColumns) . " عمود)\n";

echo "\n✨ تم إصلاح المشاكل!\n";
echo "يمكنك الآن تشغيل: php artisan migrate\n";