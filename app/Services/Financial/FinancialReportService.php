<?php

namespace App\Services\Financial;

use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\PropertyRepair;
use App\Models\Transaction;
use App\Models\Property;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FinancialReportService
{
    public function generatePropertyFinancialStatement(int $propertyId, string $startDate, string $endDate): array
    {
        $property = Property::find($propertyId);
        
        if (!$property) {
            throw new \Exception('Property not found');
        }

        // Collection payments (income)
        $collectionPayments = CollectionPayment::where('property_id', $propertyId)
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->whereHas('paymentStatus', function($q) {
                $q->where('is_paid_status', true);
            })->get();

        $totalIncome = $collectionPayments->sum('total_amount');
        $lateFees = $collectionPayments->sum('late_fee');

        // Supply payments (expenses to owner)
        $supplyPayments = SupplyPayment::whereHas('propertyContract', function($q) use ($propertyId) {
            $q->where('property_id', $propertyId);
        })
        ->whereBetween('paid_date', [$startDate, $endDate])
        ->where('supply_status', 'collected')
        ->get();

        $totalOwnerPayments = $supplyPayments->sum('net_amount');
        $totalCommissions = $supplyPayments->sum('commission_amount');

        // Maintenance expenses
        $maintenanceExpenses = PropertyRepair::where('property_id', $propertyId)
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $totalMaintenance = $maintenanceExpenses->sum('total_cost');

        // Calculate net profit
        $netProfit = $totalIncome - $totalOwnerPayments - $totalMaintenance;

        return [
            'property' => $property->toArray(),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'income' => [
                'rental_income' => $totalIncome - $lateFees,
                'late_fees' => $lateFees,
                'total_income' => $totalIncome,
                'payment_count' => $collectionPayments->count(),
            ],
            'expenses' => [
                'owner_payments' => $totalOwnerPayments,
                'maintenance_costs' => $totalMaintenance,
                'total_expenses' => $totalOwnerPayments + $totalMaintenance,
            ],
            'management' => [
                'commission_earned' => $totalCommissions,
                'commission_rate' => $supplyPayments->avg('commission_rate'),
            ],
            'profitability' => [
                'gross_profit' => $totalIncome,
                'net_profit' => $netProfit,
                'profit_margin' => $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0,
            ],
            'collections' => $collectionPayments->toArray(),
            'supply_payments' => $supplyPayments->toArray(),
            'maintenance' => $maintenanceExpenses->toArray(),
        ];
    }

    public function generateOwnerStatement(int $ownerId, string $startDate, string $endDate): array
    {
        $supplyPayments = SupplyPayment::where('owner_id', $ownerId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->with(['propertyContract.property'])
            ->get();

        $totalGrossAmount = $supplyPayments->sum('gross_amount');
        $totalCommissions = $supplyPayments->sum('commission_amount');
        $totalDeductions = $supplyPayments->sum('maintenance_deduction') + $supplyPayments->sum('other_deductions');
        $totalNetAmount = $supplyPayments->sum('net_amount');

        $paymentsByProperty = $supplyPayments->groupBy('propertyContract.property.name');

        return [
            'owner_id' => $ownerId,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_gross_amount' => $totalGrossAmount,
                'total_commissions' => $totalCommissions,
                'total_deductions' => $totalDeductions,
                'total_net_amount' => $totalNetAmount,
                'payment_count' => $supplyPayments->count(),
                'properties_count' => $paymentsByProperty->count(),
            ],
            'by_property' => $paymentsByProperty->map(function($payments) {
                return [
                    'payment_count' => $payments->count(),
                    'gross_amount' => $payments->sum('gross_amount'),
                    'commissions' => $payments->sum('commission_amount'),
                    'deductions' => $payments->sum('maintenance_deduction') + $payments->sum('other_deductions'),
                    'net_amount' => $payments->sum('net_amount'),
                ];
            })->toArray(),
            'payments' => $supplyPayments->toArray(),
        ];
    }

    public function generateCollectionReport(array $filters = []): array
    {
        $query = CollectionPayment::query();

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('due_date_end', [$filters['date_from'], $filters['date_to']]);
        }

        if (isset($filters['status_id'])) {
            $query->where('payment_status_id', $filters['status_id']);
        }

        $payments = $query->with(['property', 'unit', 'tenant', 'paymentStatus', 'paymentMethod'])->get();

        $collectedPayments = $payments->filter(function($payment) {
            return $payment->paymentStatus->is_paid_status;
        });

        $overduePayments = $payments->filter(function($payment) {
            return $payment->paymentStatus->slug === 'overdue';
        });

        return [
            'summary' => [
                'total_payments' => $payments->count(),
                'total_amount' => $payments->sum('total_amount'),
                'collected_count' => $collectedPayments->count(),
                'collected_amount' => $collectedPayments->sum('total_amount'),
                'overdue_count' => $overduePayments->count(),
                'overdue_amount' => $overduePayments->sum('total_amount'),
                'late_fees_total' => $payments->sum('late_fee'),
                'collection_rate' => $payments->count() > 0 ? ($collectedPayments->count() / $payments->count()) * 100 : 0,
            ],
            'by_property' => $payments->groupBy('property.name')->map(function($propertyPayments) {
                $collected = $propertyPayments->filter(fn($p) => $p->paymentStatus->is_paid_status);
                return [
                    'payment_count' => $propertyPayments->count(),
                    'total_amount' => $propertyPayments->sum('total_amount'),
                    'collected_count' => $collected->count(),
                    'collected_amount' => $collected->sum('total_amount'),
                    'collection_rate' => $propertyPayments->count() > 0 ? ($collected->count() / $propertyPayments->count()) * 100 : 0,
                ];
            })->toArray(),
            'by_month' => $payments->groupBy('month_year')->map(function($monthlyPayments) {
                return [
                    'payment_count' => $monthlyPayments->count(),
                    'total_amount' => $monthlyPayments->sum('total_amount'),
                    'collected_amount' => $monthlyPayments->filter(fn($p) => $p->paymentStatus->is_paid_status)->sum('total_amount'),
                ];
            })->toArray(),
            'payments' => $payments->toArray(),
        ];
    }

    public function generateMaintenanceReport(array $filters = []): array
    {
        $query = PropertyRepair::query();

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('repair_category_id', $filters['category_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('maintenance_date', [$filters['date_from'], $filters['date_to']]);
        }

        $repairs = $query->with(['property', 'unit', 'repairCategory', 'assignedEmployee'])->get();

        return [
            'summary' => [
                'total_repairs' => $repairs->count(),
                'total_cost' => $repairs->sum('total_cost'),
                'avg_cost' => $repairs->avg('total_cost'),
                'completed_repairs' => $repairs->where('status', 'completed')->count(),
                'pending_repairs' => $repairs->whereIn('status', ['reported', 'scheduled'])->count(),
                'warranty_repairs' => $repairs->where('is_under_warranty', true)->count(),
            ],
            'by_category' => $repairs->groupBy('repairCategory.name')->map(function($categoryRepairs) {
                return [
                    'repair_count' => $categoryRepairs->count(),
                    'total_cost' => $categoryRepairs->sum('total_cost'),
                    'avg_cost' => $categoryRepairs->avg('total_cost'),
                    'completed_count' => $categoryRepairs->where('status', 'completed')->count(),
                ];
            })->toArray(),
            'by_priority' => $repairs->groupBy('priority')->map(function($priorityRepairs) {
                return [
                    'repair_count' => $priorityRepairs->count(),
                    'total_cost' => $priorityRepairs->sum('total_cost'),
                    'avg_completion_days' => $priorityRepairs->filter(function($r) {
                        return $r->completion_date && $r->maintenance_date;
                    })->map(function($r) {
                        return $r->maintenance_date->diffInDays($r->completion_date);
                    })->avg(),
                ];
            })->toArray(),
            'repairs' => $repairs->toArray(),
        ];
    }

    public function generateCashFlowReport(int $propertyId, string $year): array
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthYear = sprintf('%s-%02d', $year, $month);
            $startDate = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Income from collection payments
            $income = CollectionPayment::where('property_id', $propertyId)
                ->whereBetween('paid_date', [$startDate, $endDate])
                ->whereHas('paymentStatus', function($q) {
                    $q->where('is_paid_status', true);
                })->sum('total_amount');

            // Expenses (owner payments + maintenance)
            $ownerPayments = SupplyPayment::whereHas('propertyContract', function($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            })
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('net_amount');

            $maintenanceExpenses = PropertyRepair::where('property_id', $propertyId)
                ->whereBetween('completion_date', [$startDate, $endDate])
                ->sum('total_cost');

            $totalExpenses = $ownerPayments + $maintenanceExpenses;
            $netCashFlow = $income - $totalExpenses;

            $monthlyData[] = [
                'month' => $month,
                'month_name' => $startDate->format('M Y'),
                'income' => $income,
                'owner_payments' => $ownerPayments,
                'maintenance_expenses' => $maintenanceExpenses,
                'total_expenses' => $totalExpenses,
                'net_cash_flow' => $netCashFlow,
            ];
        }

        $totalIncome = collect($monthlyData)->sum('income');
        $totalExpenses = collect($monthlyData)->sum('total_expenses');

        return [
            'property_id' => $propertyId,
            'year' => $year,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_cash_flow' => $totalIncome - $totalExpenses,
                'avg_monthly_income' => $totalIncome / 12,
                'avg_monthly_expenses' => $totalExpenses / 12,
            ],
            'monthly_data' => $monthlyData,
        ];
    }

    public function getDashboardMetrics(): array
    {
        $currentMonth = now()->format('Y-m');
        
        // Current month collections
        $currentMonthCollections = CollectionPayment::where('month_year', $currentMonth)
            ->whereHas('paymentStatus', function($q) {
                $q->where('is_paid_status', true);
            })->sum('total_amount');

        // Overdue payments
        $overduePayments = CollectionPayment::where('due_date_end', '<', now())
            ->whereHas('paymentStatus', function($q) {
                $q->where('is_paid_status', false);
            })->get();

        // Pending approvals
        $pendingApprovals = SupplyPayment::where('approval_status', 'pending')->count();

        // Active maintenance requests
        $activeMaintenance = PropertyRepair::whereIn('status', ['reported', 'scheduled', 'in_progress'])->count();

        return [
            'current_month_collections' => $currentMonthCollections,
            'overdue_payments' => [
                'count' => $overduePayments->count(),
                'total_amount' => $overduePayments->sum('total_amount'),
            ],
            'pending_approvals' => $pendingApprovals,
            'active_maintenance' => $activeMaintenance,
            'collection_rate' => $this->calculateCurrentCollectionRate(),
        ];
    }

    private function calculateCurrentCollectionRate(): float
    {
        $currentMonth = now()->format('Y-m');
        
        $totalPayments = CollectionPayment::where('month_year', $currentMonth)->count();
        $collectedPayments = CollectionPayment::where('month_year', $currentMonth)
            ->whereHas('paymentStatus', function($q) {
                $q->where('is_paid_status', true);
            })->count();

        return $totalPayments > 0 ? ($collectedPayments / $totalPayments) * 100 : 0;
    }
}