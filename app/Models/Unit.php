<?php

namespace App\Models;

use App\Enums\UnitOccupancyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'property_id',
        'unit_type_id',
        'unit_category_id',
        'rooms_count',
        'bathrooms_count',
        'balconies_count',
        'floor_number',
        'has_laundry_room',
        'electricity_account_number',
        'water_expenses',
        'floor_plan_file',
        'area_sqm',
        'rent_price',
        'notes',
    ];

    protected $casts = [
        'area_sqm' => 'decimal:2',
        'rent_price' => 'decimal:2',
        'water_expenses' => 'decimal:2',
        'floor_number' => 'integer',
        'rooms_count' => 'integer',
        'bathrooms_count' => 'integer',
        'balconies_count' => 'integer',
        'has_laundry_room' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function unitCategory(): BelongsTo
    {
        return $this->belongsTo(UnitCategory::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(UnitFeature::class, 'unit_unit_feature')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(UnitContract::class);
    }

    /**
     * Get the active contract for this unit (currently running today)
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(UnitContract::class)
            ->where('contract_status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest();
    }

    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'subject');
    }

    /**
     * حساب إجمالي النفقات للوحدة
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses()->sum('cost');
    }

    /**
     * حساب نفقات الشهر الحالي للوحدة
     */
    public function getCurrentMonthExpensesAttribute(): float
    {
        return (float) $this->expenses()->thisMonth()->sum('cost');
    }

    /**
     * الحصول على حالة إشغال الوحدة
     */
    public function getOccupancyStatusAttribute(): UnitOccupancyStatus
    {
        return UnitOccupancyStatus::fromUnit($this);
    }

    /**
     * Check if unit is available for rental.
     */
    public function isAvailable(): bool
    {
        return $this->activeContract === null;
    }

    /**
     * Check if unit is occupied.
     */
    public function isOccupied(): bool
    {
        return $this->activeContract !== null;
    }

    /**
     * Get current tenant from active contract.
     */
    public function getCurrentTenantAttribute(): ?User
    {
        return $this->activeContract?->tenant;
    }
}
