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
        'type_id',
        'status_id',
        'location_id',
        'address', 
        'postal_code', 
        'parking_spots', 
        'elevators',
        'build_year', 
        'floors_count', 
        'notes'
    ];
    
    protected $casts = [
        'build_year' => 'integer',
        'parking_spots' => 'integer',
        'elevators' => 'integer',
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
    
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'type_id');
    }
    
    public function propertyStatus(): BelongsTo
    {
        return $this->belongsTo(PropertyStatus::class, 'status_id');
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
        return $this->belongsToMany(PropertyFeature::class, 'property_property_feature')
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
    
}
