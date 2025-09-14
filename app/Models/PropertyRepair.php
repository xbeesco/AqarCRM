<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyRepair extends Model
{
    protected $fillable = [
        'repair_number',
        'repair_category_id',
        'property_id',
        'unit_id',
        'title',
        'description',
        'total_cost',
        'maintenance_date',
        'scheduled_date',
        'completion_date',
        'status',
        'priority',
        'assigned_to',
        'vendor_name',
        'vendor_phone',
        'is_under_warranty',
        'warranty_expires_at',
        'work_notes',
        'cost_breakdown',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'maintenance_date' => 'date',
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'warranty_expires_at' => 'date',
        'is_under_warranty' => 'boolean',
        'cost_breakdown' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($repair) {
            if (empty($repair->repair_number)) {
                $repair->repair_number = self::generateRepairNumber();
            }
        });
    }

    // Relationships
    public function repairCategory(): BelongsTo
    {
        return $this->belongsTo(RepairCategory::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUnderWarranty($query)
    {
        return $query->where('is_under_warranty', true)
                    ->where('warranty_expires_at', '>=', now());
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['reported', 'scheduled']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Methods
    public static function generateRepairNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'REPAIR-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function isUnderWarranty(): bool
    {
        if (!$this->is_under_warranty || !$this->warranty_expires_at) {
            return false;
        }

        return $this->warranty_expires_at->isFuture();
    }

    public function calculateCostImpact(): array
    {
        $breakdown = $this->cost_breakdown ?? [];
        
        return [
            'labor_cost' => $breakdown['labor'] ?? 0,
            'material_cost' => $breakdown['materials'] ?? 0,
            'vendor_cost' => $breakdown['vendor'] ?? 0,
            'total_cost' => $this->total_cost,
        ];
    }

    public function scheduleNextMaintenance(): void
    {
        // Logic for recurring maintenance
        if (isset($this->cost_breakdown['recurring_months'])) {
            $nextDate = $this->completion_date?->addMonths($this->cost_breakdown['recurring_months']);
            
            if ($nextDate) {
                self::create([
                    'repair_category_id' => $this->repair_category_id,
                    'property_id' => $this->property_id,
                    'unit_id' => $this->unit_id,
                    'title' => 'Scheduled: ' . $this->title,
                    'description' => 'Recurring maintenance - ' . $this->description,
                    'total_cost' => $this->total_cost,
                    'maintenance_date' => $nextDate,
                    'status' => 'scheduled',
                    'priority' => 'medium',
                ]);
            }
        }
    }

    public function complete($completionNotes = null): bool
    {
        $this->update([
            'status' => 'completed',
            'completion_date' => now()->toDateString(),
            'work_notes' => $completionNotes ?: $this->work_notes,
        ]);

        // Schedule next maintenance if recurring
        $this->scheduleNextMaintenance();

        return true;
    }

    public function assignToVendor($vendorName, $vendorPhone = null): bool
    {
        $this->update([
            'vendor_name' => $vendorName,
            'vendor_phone' => $vendorPhone,
            'status' => 'scheduled',
        ]);

        return true;
    }
}