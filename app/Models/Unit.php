<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * Get the property that owns the unit
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the unit type
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    /**
     * Get the unit category
     */
    public function unitCategory(): BelongsTo
    {
        return $this->belongsTo(UnitCategory::class);
    }

    /**
     * Get the features associated with the unit
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(UnitFeature::class, 'unit_unit_feature')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    /**
     * Get all contracts for this unit
     */
    public function contracts()
    {
        return $this->hasMany(UnitContract::class);
    }

    /**
     * Get the active contract for this unit
     */
    public function activeContract()
    {
        return $this->hasOne(UnitContract::class)
                    ->where('contract_status', 'active')
                    ->latest();
    }

    /**
     * النفقات المرتبطة بالوحدة
     */
    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'subject');
    }
    
    /**
     * حساب إجمالي النفقات للوحدة
     */
    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('cost');
    }
    
    /**
     * حساب نفقات الشهر الحالي للوحدة
     */
    public function getCurrentMonthExpensesAttribute(): float
    {
        return $this->expenses()->thisMonth()->sum('cost');
    }
}