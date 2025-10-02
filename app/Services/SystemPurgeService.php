<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Enums\UserType;

class SystemPurgeService
{
    /**
     * Purge system data by scope.
     *
     * Scopes:
     * - financial: payments and expenses only
     * - financial_contracts: financial + unit/property contracts
     * - financial_contracts_properties: previous + properties, units, tenants/owners
     * - all: everything above + foundational setup tables
     */
    public function purge(string $scope): array
    {
        $scope = $this->normalizeScope($scope);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $summary = [];

        // 1) Financial tables
        if (in_array($scope, ['financial', 'financial_contracts', 'financial_contracts_properties', 'all'])) {
            $summary[] = $this->truncateTables([
                'collection_payments',
                'supply_payments',
                'expenses',
            ], 'البيانات المالية');
        }

        // 2) Contracts tables
        if (in_array($scope, ['financial_contracts', 'financial_contracts_properties', 'all'])) {
            $summary[] = $this->truncateTables([
                'unit_contracts',
                'property_contracts',
            ], 'عقود التأجير والملكية');
        }

        // 3) Properties & Units
        if (in_array($scope, ['financial_contracts_properties', 'all'])) {
            $summary[] = $this->truncateTables([
                'properties',
                'units',
            ], 'العقارات والوحدات');

            // Remove tenants and owners (users table, not truncated)
            User::byType('clients')->forceDelete();
            $summary[] = 'تم حذف الملاك والمستأجرين من جدول المستخدمين (حذف دائم)';
        }

        // 4) Foundational setup tables
        if ($scope === 'all') {
            $summary[] = $this->truncateTables([
                'locations',
                'unit_features',
                'unit_statuses',
                'unit_types',
                'unit_categories',
                'property_features',
                'property_statuses',
                'property_types',
            ], 'بيانات التأسيس (المواقع والأنواع والميزات والحالات)');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $summary;
    }

    private function normalizeScope(string $scope): string
    {
        return match ($scope) {
            'financial' => 'financial',
            'financial_contracts' => 'financial_contracts',
            'financial_contracts_properties' => 'financial_contracts_properties',
            'all', 'everything', 'complete' => 'all',
            default => 'financial',
        };
    }

    private function truncateTables(array $tables, string $label): string
    {
        foreach ($tables as $table) {
            DB::statement("TRUNCATE TABLE `{$table}`");
        }
        return "تم تفريغ {$label} وإعادة الترقيم بدءًا من 1";
    }
}