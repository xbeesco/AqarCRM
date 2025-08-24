<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔍 فحص حالة الجداول في قاعدة البيانات...\n\n";

// الجداول المتوقعة بناءً على موارد Filament
$expectedTables = [
    'users',
    'properties',
    'property_types',
    'property_features',
    'property_statuses',
    'units',
    'unit_types',
    'unit_categories',
    'unit_contracts',
    'property_contracts',
    'locations',
    'employees',
    'owners',
    'tenants',
];

echo "📊 حالة الجداول:\n";
echo str_repeat("-", 50) . "\n";

foreach ($expectedTables as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "✅ $table - موجود ($count سجل)\n";
        
        // عرض الأعمدة الموجودة
        $columns = Schema::getColumnListing($table);
        echo "   الأعمدة: " . implode(', ', array_slice($columns, 0, 5));
        if (count($columns) > 5) {
            echo "... (" . count($columns) . " عمود)";
        }
        echo "\n";
    } else {
        echo "❌ $table - غير موجود\n";
    }
}

echo str_repeat("-", 50) . "\n";

// التحقق من الجداول الإضافية
$allTables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$tableKey = "Tables_in_" . $dbName;

echo "\n📋 جميع الجداول في قاعدة البيانات:\n";
foreach ($allTables as $table) {
    $tableName = $table->$tableKey;
    if (!in_array($tableName, $expectedTables) && !str_starts_with($tableName, 'migrations')) {
        echo "   • $tableName\n";
    }
}