<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'name', 
        'owner_id', 
        'status', 
        'type', 
        'location_id',
        'address', 
        'postal_code', 
        'parking_spots', 
        'elevators', 
        'area_sqm', 
        'build_year', 
        'floors_count', 
        'notes'
    ];
    
    protected $casts = [
        'build_year' => 'integer',
        'area_sqm' => 'decimal:2',
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
    
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
    
    public function contracts(): HasMany
    {
        return $this->hasMany(PropertyContract::class);
    }
    
    public function getOccupancyRate(): float
    {
        $totalUnits = $this->units()->count();
        if ($totalUnits === 0) return 0;
        
        $occupiedUnits = $this->units()->whereNotNull('tenant_id')->count();
        return ($occupiedUnits / $totalUnits) * 100;
    }
    
    public function getMonthlyRevenue(): float
    {
        return $this->units()
            ->whereNotNull('tenant_id')
            ->sum('rent_price');
    }
    
    public function getAvailableUnits(): int
    {
        return $this->units()
            ->whereNull('tenant_id')
            ->count();
    }
}
