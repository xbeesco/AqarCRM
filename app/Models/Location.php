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
    
    /**
     * Get the level label in Arabic
     */
    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            1 => 'منطقة',
            2 => 'مدينة',
            3 => 'مركز',
            4 => 'حي',
            default => 'غير محدد'
        };
    }
    
    /**
     * Get the full path of the location
     */
    public function getFullPathAttribute(): string
    {
        $path = collect();
        $current = $this;
        
        while ($current) {
            $path->prepend($current->name_ar ?? $current->name);
            $current = $current->parent;
        }
        
        return $path->join(' > ');
    }
    
    /**
     * Get breadcrumbs for the location
     */
    public function getBreadcrumbsAttribute(): array
    {
        $breadcrumbs = collect();
        $current = $this;
        
        while ($current) {
            $breadcrumbs->prepend([
                'id' => $current->id,
                'name' => $current->name_ar ?? $current->name,
                'level' => $current->level,
                'level_label' => $current->level_label
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumbs->toArray();
    }
    
    /**
     * Get localized name based on app locale
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' 
            ? ($this->name_ar ?? $this->name)
            : ($this->name_en ?? $this->name);
    }
    
    /**
     * Get level options for select
     */
    public static function getLevelOptions(): array
    {
        return [
            1 => 'منطقة',
            2 => 'مدينة', 
            3 => 'مركز',
            4 => 'حي'
        ];
    }
    
    /**
     * Get parent options based on level
     */
    public static function getParentOptions(int $level): array
    {
        if ($level <= 1) {
            return [];
        }
        
        return self::where('level', $level - 1)
            ->active()
            ->pluck('name_ar', 'id')
            ->toArray();
    }
}
