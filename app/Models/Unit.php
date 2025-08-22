<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Unit extends Model
{
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
        'status',
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

    protected $attributes = [
        'status' => 'available',
        'has_laundry_room' => false,
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
     * Get the current tenant of the unit
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
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
     * Scope for active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for available units
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope for occupied units
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    /**
     * Scope by property
     */
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope by unit type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('unit_type', $type);
    }

    /**
     * Scope by price range
     */
    public function scopePriceRange($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice) {
            $query->where('rent_price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('rent_price', '<=', $maxPrice);
        }
        return $query;
    }

    /**
     * Get unit code attribute
     */
    public function getUnitCodeAttribute(): string
    {
        return "PROP-{$this->property_id}-U{$this->unit_number}";
    }

    /**
     * Check if unit is available
     */
    public function isAvailable(): bool
    {
        return $this->status && 
               $this->status->is_available && 
               $this->status->allows_tenant_assignment &&
               !$this->current_tenant_id &&
               $this->is_active;
    }

    /**
     * Check if unit is occupied
     */
    public function isOccupied(): bool
    {
        return !is_null($this->current_tenant_id);
    }

    /**
     * Check if unit is under maintenance
     */
    public function isUnderMaintenance(): bool
    {
        return $this->status && $this->status->requires_maintenance;
    }

    /**
     * Assign tenant to unit
     */
    public function assignTenant(User $tenant, $startDate = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $this->current_tenant_id = $tenant->id;
        
        // Update status to occupied if available
        if ($this->status && $this->status->slug === 'available') {
            $occupiedStatus = UnitStatus::where('slug', 'occupied')->first();
            if ($occupiedStatus) {
                $this->status_id = $occupiedStatus->id;
            }
        }

        return $this->save();
    }

    /**
     * Release tenant from unit
     */
    public function releaseTenant($endDate = null): bool
    {
        $this->current_tenant_id = null;
        
        // Update status to available if occupied
        if ($this->status && $this->status->slug === 'occupied') {
            $availableStatus = UnitStatus::where('slug', 'available')->first();
            if ($availableStatus) {
                $this->status_id = $availableStatus->id;
            }
        }

        $this->available_from = $endDate ?: now()->toDateString();

        return $this->save();
    }

    /**
     * Calculate pricing for different periods
     */
    public function calculatePrice(string $period = 'monthly'): float
    {
        $basePrice = $this->rent_price;

        switch ($period) {
            case 'weekly':
                return $basePrice / 4.33; // Average weeks per month
            case 'quarterly':
                return $basePrice * 3;
            case 'semi_annual':
                return $basePrice * 6;
            case 'annual':
                return $basePrice * 12;
            case 'monthly':
            default:
                return $basePrice;
        }
    }

    /**
     * Get unit type options
     */
    public static function getUnitTypeOptions(): array
    {
        return [
            'studio' => 'ستوديو / Studio',
            'apartment' => 'شقة / Apartment',
            'duplex' => 'دوبلكس / Duplex',
            'penthouse' => 'بنت هاوس / Penthouse',
            'office' => 'مكتب / Office',
            'shop' => 'محل تجاري / Shop',
            'warehouse' => 'مستودع / Warehouse',
        ];
    }

    /**
     * Get unit ranking options
     */
    public static function getUnitRankingOptions(): array
    {
        return [
            'economy' => 'اقتصادي / Economy',
            'standard' => 'عادي / Standard',
            'premium' => 'مميز / Premium',
            'luxury' => 'فاخر / Luxury',
        ];
    }

    /**
     * Get direction options
     */
    public static function getDirectionOptions(): array
    {
        return [
            'north' => 'شمال / North',
            'south' => 'جنوب / South',
            'east' => 'شرق / East',
            'west' => 'غرب / West',
            'northeast' => 'شمال شرق / Northeast',
            'northwest' => 'شمال غرب / Northwest',
            'southeast' => 'جنوب شرق / Southeast',
            'southwest' => 'جنوب غرب / Southwest',
        ];
    }

    /**
     * Get view type options
     */
    public static function getViewTypeOptions(): array
    {
        return [
            'street' => 'شارع / Street',
            'garden' => 'حديقة / Garden',
            'sea' => 'بحر / Sea',
            'city' => 'مدينة / City',
            'mountain' => 'جبل / Mountain',
            'courtyard' => 'فناء داخلي / Courtyard',
        ];
    }

    /**
     * Get rooms and bathrooms display
     */
    public function getRoomsBathroomsDisplayAttribute(): string
    {
        return "{$this->rooms_count}R/{$this->bathrooms_count}B";
    }

    /**
     * Get formatted area display
     */
    public function getAreaDisplayAttribute(): string
    {
        return "{$this->area_sqm} م²";
    }

    /**
     * Get formatted rent display
     */
    public function getRentDisplayAttribute(): string
    {
        return number_format($this->rent_price, 2) . ' SAR';
    }
}
