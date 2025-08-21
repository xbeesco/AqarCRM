<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'owner_id', 
        'status', 
        'type', 
        'location_id',
        'address', 
        'latitude',
        'longitude',
        'postal_code', 
        'parking_spots', 
        'elevators',
        'has_elevator',
        'area_sqm', 
        'garden_area',
        'build_year', 
        'floors_count', 
        'notes'
    ];
    
    protected $casts = [
        'build_year' => 'integer',
        'area_sqm' => 'decimal:2',
        'garden_area' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'parking_spots' => 'integer',
        'elevators' => 'integer',
        'has_elevator' => 'boolean',
        'floors_count' => 'integer',
    ];
    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
    
    public function contracts(): HasMany
    {
        return $this->hasMany(PropertyContract::class);
    }
    
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(PropertyFeature::class, 'property_feature_property')
                    ->withPivot('value')
                    ->withTimestamps();
    }
    
    public function getOccupancyRateAttribute(): float
    {
        $totalUnits = $this->units()->count();
        if ($totalUnits === 0) return 0;
        
        $occupiedUnits = $this->units()->whereNotNull('current_tenant_id')->count();
        return ($occupiedUnits / $totalUnits) * 100;
    }
    
    public function getMonthlyRevenueAttribute(): float
    {
        return $this->units()
            ->whereNotNull('current_tenant_id')
            ->sum('rent_price');
    }
    
    public function getTotalUnitsAttribute(): int
    {
        return $this->units()->count();
    }
    
    public function getAvailableUnits(): int
    {
        return $this->units()
            ->whereNull('current_tenant_id')
            ->count();
    }
    
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ];
        }
        return null;
    }
}
