<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'parent_id', 
        'level',
        'path',
        'coordinates',
        'postal_code',
        'is_active'
    ];
    
    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($location) {
            $location->updatePath();
            if ($location->wasChanged('path')) {
                $location->saveQuietly();
            }
        });
    }
    
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
            $path->prepend($current->name);
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
                'name' => $current->name,
                'level' => $current->level,
                'level_label' => $current->level_label
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumbs->toArray();
    }
    
    /**
     * Get the name attribute (defaults to Arabic)
     */
    public function getNameAttribute(): string
    {
        return $this->name_ar ?: $this->name_en ?: '';
    }

    /**
     * Get localized name based on app locale
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        
        if ($locale === 'en') {
            return $this->name_en ?: $this->name_ar ?: '';
        }
        
        return $this->name_ar ?: $this->name_en ?: '';
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
            ->pluck('name_ar', 'id')
            ->toArray();
    }
    
    /**
     * Update the path field for hierarchical ordering
     */
    public function updatePath(): void
    {
        if ($this->id) {
            if ($this->parent_id) {
                $parent = self::find($this->parent_id);
                
                // Get siblings count to determine the order within parent
                $siblingOrder = self::where('parent_id', $this->parent_id)
                    ->where('id', '<=', $this->id)
                    ->orderBy('id')
                    ->count();
                
                $newPath = ($parent?->path ?? '') . '/' . str_pad($siblingOrder, 4, '0', STR_PAD_LEFT);
            } else {
                // For root level, get order among all root items
                $rootOrder = self::whereNull('parent_id')
                    ->where('id', '<=', $this->id)
                    ->orderBy('id')
                    ->count();
                
                $newPath = '/' . str_pad($rootOrder, 4, '0', STR_PAD_LEFT);
            }
            
            if ($this->path !== $newPath) {
                $this->path = $newPath;
                
                // Update children paths when parent path changes
                $this->updateChildrenPaths();
            }
        }
    }
    
    /**
     * Update all children paths when parent path changes
     */
    public function updateChildrenPaths(): void
    {
        $children = $this->children;
        foreach ($children as $child) {
            $child->updatePath();
            $child->saveQuietly();
        }
    }
    
    /**
     * Get all descendants in hierarchical order
     */
    public static function getHierarchicalOrder()
    {
        return self::orderBy('path')->get();
    }
}
