<?php

/**
 * سكريبت لتخطي جميع الترحيلات المشكلة
 * يسجلها كـ "تم تشغيلها" لتجنب المشاكل
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "⚡ تخطي الترحيلات المشكلة...\n\n";

// قائمة جميع الترحيلات التي تسبب مشاكل
$problematicMigrations = [
    '2025_08_22_180803_remove_unnecessary_fields_from_properties_table',
    '2025_08_22_183132_add_has_elevator_to_properties_table',
    '2025_08_22_184735_fix_elevators_and_parking_spots_defaults',
    '2025_08_22_184849_remove_has_elevator_from_properties',
    '2025_08_22_191500_create_unit_types_table',
    '2025_08_22_191518_create_unit_categories_table',
    '2025_08_22_191745_create_unit_unit_feature_table',
    '2025_08_22_203231_make_unit_fields_nullable',
    '2025_08_22_211652_update_property_contracts_table_structure',
    '2025_08_22_212103_remove_old_fields_from_property_contracts',
    '2025_08_22_212210_cleanup_property_contracts_table',
    '2025_08_22_215709_add_payments_count_to_property_contracts_table',
];

$skipped = 0;

foreach ($problematicMigrations as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 999
        ]);
        echo "⏭️  تخطي: $migration\n";
        $skipped++;
    } else {
        echo "✓ مُشغّل مسبقاً: $migration\n";
    }
}

echo "\n📊 الملخص:\n";
echo "   • تم تخطي: $skipped ترحيل\n";
echo "   • إجمالي المفحوص: " . count($problematicMigrations) . "\n";

if ($skipped > 0) {
    echo "\n✨ تم التخطي بنجاح!\n";
    echo "يمكنك الآن تشغيل الترحيلات الجديدة النظيفة.\n";
} else {
    echo "\n✅ لا يوجد شيء للتخطي.\n";
}