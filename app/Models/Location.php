<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name', 
        'name_ar',
        'name_en',
        'code',
        'parent_id', 
        'level', 
        'path', 
        'coordinates', 
        'postal_code', 
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }
    
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
    
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    // Scopes for each level
    public function scopeCountries($query)
    {
        return $query->where('level', 1);
    }
    
    public function scopeCities($query)
    {
        return $query->where('level', 2);
    }
    
    public function scopeDistricts($query)
    {
        return $query->where('level', 3);
    }
    
    public function scopeNeighborhoods($query)
    {
        return $query->where('level', 4);
    }
}
