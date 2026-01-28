<?php

namespace App\Services\Financial;

use Exception;
use App\Models\PropertyRepair;
use App\Models\RepairCategory;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Collection;

class MaintenanceService
{
    public function createMaintenanceRequest(array $data): PropertyRepair
    {
        // Validate repair category affects property or unit
        $category = RepairCategory::find($data['repair_category_id']);
        
        if (!$category) {
            throw new Exception('Invalid repair category');
        }

        // Auto-populate property_id if unit_id is provided
        if (isset($data['unit_id']) && !isset($data['property_id'])) {
            $unit = Unit::find($data['unit_id']);
            if ($unit) {
                $data['property_id'] = $unit->property_id;
            }
        }

        // Validate that the category matches the repair scope
        if ($category->affects_property && !isset($data['property_id'])) {
            throw new Exception('Property is required for this repair category');
        }

        if ($category->affects_unit && !isset($data['unit_id'])) {
            throw new Exception('Unit is required for this repair category');
        }

        return PropertyRepair::create($data);
    }

    public function assignToVendor(PropertyRepair $repair, string $vendorName, ?string $vendorPhone = null): bool
    {
        return $repair->assignToVendor($vendorName, $vendorPhone);
    }

    public function assignToEmployee(PropertyRepair $repair, int $employeeId): bool
    {
        $repair->update([
            'assigned_to' => $employeeId,
            'status' => 'scheduled',
        ]);

        return true;
    }

    public function trackProgress(PropertyRepair $repair, string $status, ?string $notes = null): bool
    {
        $validStatuses = ['reported', 'scheduled', 'in_progress', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }

        $updateData = ['status' => $status];

        if ($notes) {
            $updateData['work_notes'] = $repair->work_notes . "\n" . now()->format('Y-m-d H:i') . ": " . $notes;
        }

        if ($status === 'completed') {
            $updateData['completion_date'] = now();
        }

        $repair->update($updateData);

        // Create transaction if completed
        if ($status === 'completed') {
            $repair->complete($notes);
        }

        return true;
    }

    public function processWarrantyClaim(PropertyRepair $repair): bool
    {
        if (!$repair->is_under_warranty) {
            throw new Exception('This repair is not under warranty');
        }

        if (!$repair->isUnderWarranty()) {
            throw new Exception('Warranty has expired');
        }

        // Set cost to zero for warranty claims
        $repair->update([
            'total_cost' => 0.00,
            'cost_breakdown' => array_merge($repair->cost_breakdown ?? [], [
                'warranty_claim' => true,
                'original_cost' => $repair->total_cost,
            ]),
            'work_notes' => ($repair->work_notes ?? '') . "\nWarranty claim processed - cost waived",
        ]);

        return true;
    }

    public function scheduleRecurringMaintenance(PropertyRepair $repair, int $intervalMonths): bool
    {
        $costBreakdown = $repair->cost_breakdown ?? [];
        $costBreakdown['recurring_months'] = $intervalMonths;
        
        $repair->update(['cost_breakdown' => $costBreakdown]);
        
        return true;
    }

    public function getMaintenanceCalendar(?int $propertyId = null, ?string $month = null): array
    {
        $query = PropertyRepair::query();

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        if ($month) {
            $query->whereMonth('scheduled_date', '=', date('m', strtotime($month)))
                  ->whereYear('scheduled_date', '=', date('Y', strtotime($month)));
        }

        $repairs = $query->with(['property', 'unit', 'repairCategory', 'assignedEmployee'])
                         ->orderBy('scheduled_date')
                         ->get();

        return [
            'repairs' => $repairs->toArray(),
            'summary' => [
                'total_repairs' => $repairs->count(),
                'scheduled' => $repairs->where('status', 'scheduled')->count(),
                'in_progress' => $repairs->where('status', 'in_progress')->count(),
                'completed' => $repairs->where('status', 'completed')->count(),
                'total_cost' => $repairs->sum('total_cost'),
            ]
        ];
    }

    public function generateMaintenanceReport(array $filters): array
    {
        $query = PropertyRepair::query();

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('repair_category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('maintenance_date', [$filters['date_from'], $filters['date_to']]);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $repairs = $query->with(['property', 'unit', 'repairCategory', 'assignedEmployee'])->get();

        // Group by category
        $byCategory = $repairs->groupBy('repairCategory.name')->map(function($categoryRepairs) {
            return [
                'count' => $categoryRepairs->count(),
                'total_cost' => $categoryRepairs->sum('total_cost'),
                'avg_cost' => $categoryRepairs->avg('total_cost'),
            ];
        });

        // Group by status
        $byStatus = $repairs->groupBy('status')->map(function($statusRepairs) {
            return [
                'count' => $statusRepairs->count(),
                'total_cost' => $statusRepairs->sum('total_cost'),
            ];
        });

        return [
            'summary' => [
                'total_repairs' => $repairs->count(),
                'total_cost' => $repairs->sum('total_cost'),
                'avg_cost' => $repairs->avg('total_cost'),
                'completed_count' => $repairs->where('status', 'completed')->count(),
                'pending_count' => $repairs->whereIn('status', ['reported', 'scheduled'])->count(),
            ],
            'by_category' => $byCategory->toArray(),
            'by_status' => $byStatus->toArray(),
            'repairs' => $repairs->toArray(),
        ];
    }

    public function getPreventiveMaintenanceDue(): Collection
    {
        // Get repairs that are due for recurring maintenance
        return PropertyRepair::where('status', 'completed')
            ->whereNotNull('cost_breakdown')
            ->get()
            ->filter(function($repair) {
                $costBreakdown = $repair->cost_breakdown;
                if (!isset($costBreakdown['recurring_months'])) {
                    return false;
                }
                
                $intervalMonths = $costBreakdown['recurring_months'];
                $nextDueDate = $repair->completion_date->addMonths($intervalMonths);
                
                return $nextDueDate->isToday() || $nextDueDate->isPast();
            });
    }

    public function estimateRepairCost(array $repairData): array
    {
        // Simple cost estimation based on category and complexity
        $baseCosts = [
            'general_maintenance' => 500,
            'special_maintenance' => 1000,
            'government_payment_unit' => 2000,
            'government_payment_prop' => 5000,
        ];

        $category = RepairCategory::find($repairData['repair_category_id']);
        $baseCost = $baseCosts[$category->slug] ?? 1000;

        $priorityMultipliers = [
            'low' => 0.8,
            'medium' => 1.0,
            'high' => 1.3,
            'urgent' => 1.5,
        ];

        $priority = $repairData['priority'] ?? 'medium';
        $multiplier = $priorityMultipliers[$priority] ?? 1.0;

        $estimatedCost = $baseCost * $multiplier;

        return [
            'estimated_cost' => round($estimatedCost, 2),
            'base_cost' => $baseCost,
            'priority_multiplier' => $multiplier,
            'breakdown' => [
                'labor' => round($estimatedCost * 0.6, 2),
                'materials' => round($estimatedCost * 0.3, 2),
                'overhead' => round($estimatedCost * 0.1, 2),
            ]
        ];
    }
}